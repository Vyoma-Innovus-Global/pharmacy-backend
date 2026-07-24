<?php

namespace App\Console\Commands;

use App\Http\Controllers\PaymentController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyPendingPayments extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'payments:verify-pending
                            {--order_id= : Verify one pending payment by order ID}
                            {--limit= : Maximum number of pending payments to process}';

    /**
     * The console command description.
     */
    protected $description = 'Verify pending pharmacy payments with SBI and save their latest status';

    /**
     * Execute the console command.
     */
    public function handle(PaymentController $paymentController): int
    {
        $parameters = array_filter([
            'order_id' => $this->option('order_id'),
            'limit' => $this->option('limit'),
        ], static fn ($value) => $value !== null && $value !== '');

        $response = $paymentController->getPendingPaymentDetails(
            Request::create('/api/payment/pending-payment-details', 'GET', $parameters)
        );

        $payload = $response->getData(true);
        $context = [
            'status_code' => $response->getStatusCode(),
            'processed' => $payload['processed'] ?? 0,
            'updated' => $payload['updated'] ?? 0,
            'failed' => $payload['failed'] ?? 0,
            'not_found' => $payload['not_found'] ?? 0,
            'message' => $payload['message'] ?? null,
        ];

        if ($response->getStatusCode() >= 400 || ($payload['error'] ?? true)) {
            Log::channel('daily')->error('[Payment Cron] Pending payment verification failed', $context);
            $this->error($payload['message'] ?? 'Pending payment verification failed.');

            return self::FAILURE;
        }

        Log::channel('daily')->info('[Payment Cron] Pending payment verification completed', $context);

        $this->info(sprintf(
            'Processed: %d, updated: %d, failed: %d, not found: %d.',
            $context['processed'],
            $context['updated'],
            $context['failed'],
            $context['not_found']
        ));

        return $context['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
