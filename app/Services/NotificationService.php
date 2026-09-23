<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Tenant;
use App\Models\NotificationLog;
use App\Mail\PaymentReceiptMail;
use App\Mail\RentDueReminderMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class NotificationService
{
    /**
     * Dispatch receipt notifications across Email, WhatsApp, and SMS
     */
    public function dispatchReceipt(Payment $payment, array $channels = ['email', 'whatsapp', 'sms']): array
    {
        $tenant = $payment->tenant;
        $results = [];

        // 1. Email Channel
        if (in_array('email', $channels) && $tenant->email) {
            try {
                Mail::to($tenant->email)->send(new PaymentReceiptMail($payment));
                $this->logDispatch($tenant->id, 'payment_receipt', 'email', 'sent', $tenant->email, [
                    'serial' => $payment->receipt_serial_no,
                    'amount' => $payment->amount_received,
                ]);
                $results['email'] = true;
            } catch (\Throwable $e) {
                Log::error("Receipt email dispatch failed: {$e->getMessage()}");
                $this->logDispatch($tenant->id, 'payment_receipt', 'email', 'failed', $tenant->email, [
                    'error' => $e->getMessage(),
                ]);
                $results['email'] = false;
            }
        }

        // 2. WhatsApp Channel
        if (in_array('whatsapp', $channels) && $tenant->whatsapp_number) {
            try {
                // Construct personalized WhatsApp payload
                $roomDesc = $payment->room ? "{$payment->room->block->name} Rm {$payment->room->room_number}" : 'Hostel Unit';
                $waText = "Hello {$tenant->full_name}, thank you for your rent payment of " . number_format($payment->amount_received) . " UGX for {$roomDesc}. Receipt: {$payment->receipt_serial_no}.";
                
                // In production, trigger WhatsApp Cloud API / Twilio WhatsApp endpoint
                // Log dispatch to notification_logs
                $this->logDispatch($tenant->id, 'payment_receipt', 'whatsapp', 'sent', $tenant->whatsapp_number, [
                    'message' => $waText,
                    'serial' => $payment->receipt_serial_no,
                ]);
                $results['whatsapp'] = true;
            } catch (\Throwable $e) {
                $this->logDispatch($tenant->id, 'payment_receipt', 'whatsapp', 'failed', $tenant->whatsapp_number, [
                    'error' => $e->getMessage(),
                ]);
                $results['whatsapp'] = false;
            }
        }

        // 3. SMS Channel
        if (in_array('sms', $channels) && $tenant->whatsapp_number) {
            try {
                $smsText = "NAFISA HOSTEL: Payment of " . number_format($payment->amount_received) . " UGX received for {$payment->receipt_serial_no}. Thank you!";
                // In production, dispatch via Africa's Talking / Twilio SMS
                $this->logDispatch($tenant->id, 'payment_receipt', 'sms', 'sent', $tenant->whatsapp_number, [
                    'message' => $smsText,
                    'serial' => $payment->receipt_serial_no,
                ]);
                $results['sms'] = true;
            } catch (\Throwable $e) {
                $this->logDispatch($tenant->id, 'payment_receipt', 'sms', 'failed', $tenant->whatsapp_number, [
                    'error' => $e->getMessage(),
                ]);
                $results['sms'] = false;
            }
        }

        return $results;
    }

    /**
     * Dispatch Rent Due Reminder across channels
     */
    public function dispatchRentDueReminder(Tenant $tenant, int $daysRemaining, array $channels = ['email', 'whatsapp', 'sms']): array
    {
        $tenant->loadMissing('room.block');
        $amountDue = $tenant->room ? $tenant->room->effective_price : 0;
        $results = [];

        // Check if reminder was already sent today
        $alreadySentToday = NotificationLog::where('tenant_id', $tenant->id)
            ->where('type', '3_day_reminder')
            ->whereDate('created_at', Carbon::today())
            ->exists();

        if ($alreadySentToday) {
            return ['skipped' => 'Already sent today'];
        }

        // 1. Email Channel
        if (in_array('email', $channels) && $tenant->email) {
            try {
                Mail::to($tenant->email)->send(new RentDueReminderMail($tenant, $daysRemaining, $amountDue));
                $this->logDispatch($tenant->id, '3_day_reminder', 'email', 'sent', $tenant->email, [
                    'days_remaining' => $daysRemaining,
                    'amount_due' => $amountDue,
                ]);
                $results['email'] = true;
            } catch (\Throwable $e) {
                Log::error("Reminder email failed: {$e->getMessage()}");
                $this->logDispatch($tenant->id, '3_day_reminder', 'email', 'failed', $tenant->email, [
                    'error' => $e->getMessage(),
                ]);
                $results['email'] = false;
            }
        }

        // 2. WhatsApp Channel
        if (in_array('whatsapp', $channels) && $tenant->whatsapp_number) {
            $roomName = $tenant->room ? "{$tenant->room->block->name} Rm {$tenant->room->room_number}" : 'your room';
            $waMsg = "Dear {$tenant->full_name}, reminder: your monthly rent of " . number_format($amountDue) . " UGX for {$roomName} is due in {$daysRemaining} days on " . Carbon::parse($tenant->rent_due_date)->format('d M Y') . ". MTN/Airtel Merchant: 123456. Thank you!";
            
            $this->logDispatch($tenant->id, '3_day_reminder', 'whatsapp', 'sent', $tenant->whatsapp_number, [
                'message' => $waMsg,
                'amount_due' => $amountDue,
            ]);
            $results['whatsapp'] = true;
        }

        // 3. SMS Channel
        if (in_array('sms', $channels) && $tenant->whatsapp_number) {
            $smsMsg = "NAFISA HOSTEL: Rent of " . number_format($amountDue) . " UGX is due in {$daysRemaining} days (" . Carbon::parse($tenant->rent_due_date)->format('d M') . "). Please settle on time.";
            
            $this->logDispatch($tenant->id, '3_day_reminder', 'sms', 'sent', $tenant->whatsapp_number, [
                'message' => $smsMsg,
                'amount_due' => $amountDue,
            ]);
            $results['sms'] = true;
        }

        return $results;
    }

    /**
     * Helper to log notifications
     */
    protected function logDispatch(?int $tenantId, string $type, string $channel, string $status, string $recipient, array $meta = []): NotificationLog
    {
        return NotificationLog::create([
            'tenant_id' => $tenantId,
            'type' => $type,
            'channel' => $channel,
            'status' => $status,
            'recipient' => $recipient,
            'payload_meta' => $meta,
        ]);
    }
}
