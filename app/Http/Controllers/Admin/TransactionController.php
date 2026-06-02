<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListTransactionRequest;
use App\Http\Requests\Admin\StoreTransactionRequest;
use App\Http\Requests\Admin\UpdateTransactionStatusRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Services\TransactionService;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function __construct(private readonly TransactionService $transactionService) {}

    public function index(ListTransactionRequest $request)
    {
        try {
            $this->authorize('viewAny', Transaction::class);

            $transactions = $this->transactionService->getAdminTransactionsPaginated(
                $request->input('transaction_status'),
                (int) ($request->input('per_page') ?? 10)
            );

            return response()->json([
                'status' => 'success',
                'message' => __('api.transactions.admin_list_retrieved'),
                'data' => TransactionResource::collection($transactions),
                'pagination' => PaginationHelper::paginate($transactions),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.transactions.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Transaction $transaction)
    {
        try {
            $this->authorize('view', $transaction);

            return response()->json([
                'status' => 'success',
                'message' => __('api.transactions.admin_retrieved'),
                'data' => new TransactionResource($transaction),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.transactions.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function store(StoreTransactionRequest $request)
    {
        try {
            $this->authorize('createForAdmin', Transaction::class);

            $data = $request->validated();
            $transaction = $this->transactionService->createForAdmin($data);

            if ($request->hasFile('image')) {
                $transaction->addMediaFromRequest('image')->toMediaCollection('proof_image');
            }

            if (($data['transaction_status'] ?? null) === 'accepted') {
                Transaction::syncClientBalance((int) $transaction->client_id);
            }

            $transaction = $transaction->fresh() ?? $transaction;
            $transaction->load('client:id,name,phone,email,type,balance');

            return response()->json([
                'status' => 'success',
                'message' => __('api.transactions.admin_created'),
                'data' => new TransactionResource($transaction),
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.transactions.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(UpdateTransactionStatusRequest $request, Transaction $transaction)
    {
        try {
            $this->authorize('changeStatus', $transaction);

            $transaction = $this->transactionService->changePendingStatus(
                $transaction,
                $request->string('transaction_status')->toString()
            );

            return response()->json([
                'status' => 'success',
                'message' => __('api.transactions.status_changed'),
                'data' => new TransactionResource($transaction),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.transactions.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // get transaction by client id
    public function getTransactionByClientId(Request $request, $clientId)
    {
        try {
            $this->authorize('viewAny', Transaction::class);
            $transaction = Transaction::query()->where('client_id', $clientId)->get();
            return response()->json([
                'status' => 'success',
                'message' => __('api.transactions.admin_list_retrieved'),
                'data' => TransactionResource::collection($transaction),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.transactions.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
