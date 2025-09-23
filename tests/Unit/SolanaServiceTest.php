<?php

use Roberts\LaravelWallets\Protocols\Solana\RpcClient;
use Roberts\LaravelWallets\Protocols\Solana\RpcException;
use Roberts\LaravelWallets\Services\Solana\SolanaService;

describe('Solana Service', function () {
    beforeEach(function () {
        $this->mockRpcClient = \Mockery::mock(RpcClient::class);
        $this->solanaService = new SolanaService($this->mockRpcClient);
    });

    afterEach(function () {
        \Mockery::close();
    });

    describe('Account Details', function () {
        it('gets account details with balance', function () {
            $pubkey = '9WzDXwBbmkg8ZTbNMqUxvQRAyrZzDsGYdLVL9zYtAWWM';
            $accountInfo = [
                'value' => [
                    'lamports' => 1000000000,
                    'owner' => '11111111111111111111111111111112',
                    'data' => ['', 'base64'],
                ],
            ];
            $balanceInfo = ['value' => 1000000000];

            $this->mockRpcClient->shouldReceive('getAccountInfo')
                ->with($pubkey)
                ->once()
                ->andReturn($accountInfo);

            $this->mockRpcClient->shouldReceive('getBalance')
                ->with($pubkey)
                ->once()
                ->andReturn($balanceInfo);

            $this->mockRpcClient->shouldReceive('lamportsToSol')
                ->with(1000000000)
                ->once()
                ->andReturn(1.0);

            $result = $this->solanaService->getAccountDetails($pubkey);

            expect($result)->toBeArray()
                ->and($result['pubkey'])->toBe($pubkey)
                ->and($result['account_info'])->toBe($accountInfo)
                ->and($result['balance']['lamports'])->toBe(1000000000)
                ->and($result['balance']['sol'])->toBe(1.0);
        });

        it('returns null for non-existent account', function () {
            $pubkey = 'InvalidPubkey';

            $this->mockRpcClient->shouldReceive('getAccountInfo')
                ->with($pubkey)
                ->once()
                ->andReturn(null);

            $result = $this->solanaService->getAccountDetails($pubkey);
            expect($result)->toBeNull();
        });
    });

    describe('Token Accounts', function () {
        it('gets token accounts for owner', function () {
            $ownerPubkey = '9WzDXwBbmkg8ZTbNMqUxvQRAyrZzDsGYdLVL9zYtAWWM';
            $tokenAccountsResponse = [
                'value' => [
                    [
                        'pubkey' => 'TokenAccount1',
                        'account' => [
                            'data' => [
                                'parsed' => [
                                    'info' => [
                                        'mint' => 'TokenMint1',
                                        'owner' => $ownerPubkey,
                                        'tokenAmount' => [
                                            'amount' => '1000000000',
                                            'decimals' => 9,
                                            'uiAmount' => 1.0,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $this->mockRpcClient->shouldReceive('getTokenAccountsByOwner')
                ->with($ownerPubkey, ['programId' => 'TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA'])
                ->once()
                ->andReturn($tokenAccountsResponse);

            $result = $this->solanaService->getTokenAccountsForOwner($ownerPubkey);

            expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($result)->toHaveCount(1)
                ->and($result->first()['pubkey'])->toBe('TokenAccount1')
                ->and($result->first()['mint'])->toBe('TokenMint1')
                ->and($result->first()['owner'])->toBe($ownerPubkey);
        });

        it('handles RPC exception gracefully', function () {
            $ownerPubkey = '9WzDXwBbmkg8ZTbNMqUxvQRAyrZzDsGYdLVL9zYtAWWM';

            $this->mockRpcClient->shouldReceive('getTokenAccountsByOwner')
                ->with($ownerPubkey, ['programId' => 'TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA'])
                ->once()
                ->andThrow(new RpcException('Network error'));

            $result = $this->solanaService->getTokenAccountsForOwner($ownerPubkey);

            expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($result)->toHaveCount(0);
        });
    });

    describe('Transaction History', function () {
        it('gets transaction history', function () {
            $address = '9WzDXwBbmkg8ZTbNMqUxvQRAyrZzDsGYdLVL9zYtAWWM';
            $limit = 5;
            $signaturesResponse = [
                [
                    'signature' => 'Signature1',
                    'slot' => 123456,
                    'blockTime' => 1640995200,
                    'confirmationStatus' => 'finalized',
                    'err' => null,
                    'memo' => null,
                ],
                [
                    'signature' => 'Signature2',
                    'slot' => 123455,
                    'blockTime' => 1640995100,
                    'confirmationStatus' => 'confirmed',
                    'err' => null,
                    'memo' => 'Test transaction',
                ],
            ];

            $this->mockRpcClient->shouldReceive('getSignaturesForAddress')
                ->with($address, ['limit' => $limit])
                ->once()
                ->andReturn($signaturesResponse);

            $result = $this->solanaService->getTransactionHistory($address, $limit);

            expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class)
                ->and($result)->toHaveCount(2)
                ->and($result->first()['signature'])->toBe('Signature1')
                ->and($result->first()['slot'])->toBe(123456)
                ->and($result->first()['confirmation_status'])->toBe('finalized');
        });
    });

    describe('Transaction Details', function () {
        it('gets transaction details', function () {
            $signature = 'TransactionSignature';
            $transactionResponse = [
                'slot' => 123456,
                'blockTime' => 1640995200,
                'transaction' => [
                    'message' => ['instructions' => []],
                    'signatures' => [$signature],
                ],
                'meta' => [
                    'err' => null,
                    'fee' => 5000,
                    'status' => ['Ok' => null],
                ],
            ];

            $this->mockRpcClient->shouldReceive('getTransaction')
                ->with($signature, [
                    'encoding' => 'jsonParsed',
                    'maxSupportedTransactionVersion' => 0,
                ])
                ->once()
                ->andReturn($transactionResponse);

            $result = $this->solanaService->getTransactionDetails($signature);

            expect($result)->toBeArray()
                ->and($result['signature'])->toBe($signature)
                ->and($result['slot'])->toBe(123456)
                ->and($result['block_time'])->toBe(1640995200)
                ->and($result['transaction'])->toBeArray()
                ->and($result['meta'])->toBeArray();
        });

        it('returns null for non-existent transaction', function () {
            $signature = 'InvalidSignature';

            $this->mockRpcClient->shouldReceive('getTransaction')
                ->with($signature, [
                    'encoding' => 'jsonParsed',
                    'maxSupportedTransactionVersion' => 0,
                ])
                ->once()
                ->andReturn(null);

            $result = $this->solanaService->getTransactionDetails($signature);
            expect($result)->toBeNull();
        });
    });

    describe('Public Key Validation', function () {
        it('validates correct Solana public keys', function () {
            $validKeys = [
                '9WzDXwBbmkg8ZTbNMqUxvQRAyrZzDsGYdLVL9zYtAWWM',
                '11111111111111111111111111111112',
                'TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA',
            ];

            foreach ($validKeys as $key) {
                expect($this->solanaService->isValidPublicKey($key))->toBeTrue();
            }
        });

        it('rejects invalid Solana public keys', function () {
            $invalidKeys = [
                '', // empty
                'short', // too short
                '9WzDXwBbmkg8ZTbNMqUxvQRAyrZzDsGYdLVL9zYtAWWM0123456789', // too long
                '9WzDXwBbmkg8ZTbNMqUxvQRAyrZzDsGYdLVL9zYtAWWM+', // invalid base58 character
                '9WzDXwBbmkg8ZTbNMqUxvQRAyrZzDsGYdLVL9zYtAWWM0', // invalid base58 character
            ];

            foreach ($invalidKeys as $key) {
                expect($this->solanaService->isValidPublicKey($key))->toBeFalse();
            }
        });
    });

    describe('Epoch Information', function () {
        it('gets current epoch info with calculations', function () {
            $epochResponse = [
                'absoluteSlot' => 123456789,
                'blockHeight' => 123456,
                'epoch' => 300,
                'slotIndex' => 216000, // Half way through epoch
                'slotsInEpoch' => 432000,
                'transactionCount' => 987654321,
            ];

            $this->mockRpcClient->shouldReceive('getEpochInfo')
                ->once()
                ->andReturn($epochResponse);

            $result = $this->solanaService->getCurrentEpochInfo();

            expect($result)->toBeArray()
                ->and($result['epoch'])->toBe(300)
                ->and($result['slotIndex'])->toBe(216000)
                ->and($result['slotsInEpoch'])->toBe(432000)
                ->and($result['progress_percent'])->toBe(50.0) // 216000/432000 * 100
                ->and($result['slots_remaining'])->toBe(216000); // 432000 - 216000
        });
    });

    describe('Network Stats', function () {
        it('gets network statistics', function () {
            $this->mockRpcClient->shouldReceive('getHealth')->once()->andReturn('ok');
            $this->mockRpcClient->shouldReceive('getVersion')->once()->andReturn(['solana-core' => '1.14.17']);
            $this->mockRpcClient->shouldReceive('getEpochInfo')->once()->andReturn(['epoch' => 300]);
            $this->mockRpcClient->shouldReceive('getSupply')->once()->andReturn(['value' => ['total' => 500000000000000000]]);
            $this->mockRpcClient->shouldReceive('getTransactionCount')->once()->andReturn(987654321);
            $this->mockRpcClient->shouldReceive('getEndpoint')->once()->andReturn('https://api.testnet.solana.com');

            $result = $this->solanaService->getNetworkStats();

            expect($result)->toBeArray()
                ->and($result['health'])->toBe('ok')
                ->and($result['version'])->toBeArray()
                ->and($result['epoch_info'])->toBeArray()
                ->and($result['supply'])->toBeArray()
                ->and($result['transaction_count'])->toBe(987654321)
                ->and($result['network_endpoint'])->toBe('https://api.testnet.solana.com');
        });

        it('handles network stats errors gracefully', function () {
            $this->mockRpcClient->shouldReceive('getHealth')
                ->once()
                ->andThrow(new RpcException('Network unreachable'));

            $this->mockRpcClient->shouldReceive('getEndpoint')
                ->once()
                ->andReturn('https://api.testnet.solana.com');

            $result = $this->solanaService->getNetworkStats();

            expect($result)->toBeArray()
                ->and($result['error'])->toBe('Network unreachable')
                ->and($result['network_endpoint'])->toBe('https://api.testnet.solana.com');
        });
    });

    describe('Token Metadata', function () {
        it('gets token metadata', function () {
            $mintAddress = 'So11111111111111111111111111111111111111112';
            $supplyResponse = [
                'context' => ['slot' => 123456],
                'value' => [
                    'amount' => '1000000000000000000',
                    'decimals' => 9,
                    'uiAmount' => 1000000000.0,
                ],
            ];
            $accountInfo = [
                'value' => [
                    'lamports' => 1461600,
                    'owner' => '11111111111111111111111111111112',
                ],
            ];

            $this->mockRpcClient->shouldReceive('getTokenSupply')
                ->with($mintAddress)
                ->once()
                ->andReturn($supplyResponse);

            $this->mockRpcClient->shouldReceive('getAccountInfo')
                ->with($mintAddress)
                ->once()
                ->andReturn($accountInfo);

            $result = $this->solanaService->getTokenMetadata($mintAddress);

            expect($result)->toBeArray()
                ->and($result['mint'])->toBe($mintAddress)
                ->and($result['supply'])->toBe($supplyResponse)
                ->and($result['account_info'])->toBe($accountInfo);
        });

        it('returns null for invalid token mint', function () {
            $mintAddress = 'InvalidMintAddress';

            $this->mockRpcClient->shouldReceive('getTokenSupply')
                ->with($mintAddress)
                ->once()
                ->andReturn(null);

            $result = $this->solanaService->getTokenMetadata($mintAddress);
            expect($result)->toBeNull();
        });
    });

    describe('Rent Exemption', function () {
        it('gets rent exemption balance', function () {
            $dataLength = 165; // Typical token account size
            $expectedBalance = 2039280; // Typical rent exemption amount

            $this->mockRpcClient->shouldReceive('getMinimumBalanceForRentExemption')
                ->with($dataLength)
                ->once()
                ->andReturn($expectedBalance);

            $result = $this->solanaService->getRentExemptionBalance($dataLength);
            expect($result)->toBe($expectedBalance);
        });

        it('checks account rent exemption status', function () {
            $pubkey = '9WzDXwBbmkg8ZTbNMqUxvQRAyrZzDsGYdLVL9zYtAWWM';
            $accountInfo = [
                'value' => [
                    'lamports' => 5000000, // 5M lamports
                    'data' => [base64_encode(str_repeat('x', 165)), 'base64'], // 165 bytes
                ],
            ];
            $minBalance = 2039280;

            $this->mockRpcClient->shouldReceive('getAccountInfo')
                ->with($pubkey)
                ->once()
                ->andReturn($accountInfo);

            $this->mockRpcClient->shouldReceive('getMinimumBalanceForRentExemption')
                ->with(165)
                ->once()
                ->andReturn($minBalance);

            $result = $this->solanaService->isAccountRentExempt($pubkey);

            expect($result)->toBeArray()
                ->and($result['exists'])->toBeTrue()
                ->and($result['rent_exempt'])->toBeTrue() // 5M > 2.039M
                ->and($result['balance'])->toBe(5000000)
                ->and($result['min_balance_required'])->toBe($minBalance)
                ->and($result['data_length'])->toBe(165);
        });

        it('handles non-existent account', function () {
            $pubkey = 'NonExistentAccount';

            $this->mockRpcClient->shouldReceive('getAccountInfo')
                ->with($pubkey)
                ->once()
                ->andReturn(null);

            $result = $this->solanaService->isAccountRentExempt($pubkey);

            expect($result)->toBeArray()
                ->and($result['exists'])->toBeFalse()
                ->and($result['rent_exempt'])->toBeFalse()
                ->and($result['balance'])->toBe(0);
        });
    });

    describe('RPC Client Access', function () {
        it('provides access to RPC client', function () {
            $client = $this->solanaService->getRpcClient();
            expect($client)->toBe($this->mockRpcClient);
        });
    });
});
