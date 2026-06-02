<?php

namespace App\Http\Controllers\Client;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ListTransactionRequest;
use App\Http\Requests\Client\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function __construct(private readonly TransactionService $transactionService) {}

    public function index(ListTransactionRequest $request)
    {
        try {
            $this->authorize('viewAny', Transaction::class);
            /** @var \App\Models\User $user */
            $user = $request->user();

            $transactions = $this->transactionService->getClientTransactionsPaginated(
                $user,
                (int) ($request->per_page ?? 10)
            );

            return response()->json([
                'status' => 'success',
                'message' => __('api.transactions.client_list_retrieved'),
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

    public function store(StoreTransactionRequest $request)
    {
        try {
            $this->authorize('createForClient', Transaction::class);
            /** @var \App\Models\User $user */
            $user = $request->user();

            $transaction = $this->transactionService->createForClient($user, $request->validated());

            if ($request->hasFile('image')) {
                $transaction->addMediaFromRequest('image')->toMediaCollection('image');
            }

            $transaction->load('client:id,name,phone,email,type,balance');

            return response()->json([
                'status' => 'success',
                'message' => __('api.transactions.created'),
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
}
