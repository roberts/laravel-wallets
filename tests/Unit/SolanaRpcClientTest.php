<?php

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Roberts\LaravelWallets\Protocols\Solana\RpcClient;
use Roberts\LaravelWallets\Protocols\Solana\RpcException;

describe('Solana RPC Client', function () {
    beforeEach(function () {
        $this->mockHandler = new MockHandler;
        $handlerStack = HandlerStack::create($this->mockHandler);
        $httpClient = new HttpClient(['handler' => $handlerStack]);

        // We need to create a custom RpcClient for testing
        $this->rpcClient = new class('https://api.testnet.solana.com') extends RpcClient
        {
            public function setMockHttpClient(HttpClient $client): void
            {
                $this->httpClient = $client;
            }
        };

        $this->rpcClient->setMockHttpClient($httpClient);
    });

    describe('Basic RPC Calls', function () {
        it('makes successful RPC call', function () {
            $expectedResult = ['value' => 123456789];

            $this->mockHandler->append(
                new Response(200, [], json_encode([
                    'jsonrpc' => '2.0',
                    'result' => $expectedResult,
                    'id' => 1,
                ]))
            );

            $result = $this->rpcClient->call('getBalance', ['9WzDXwBbmkg8ZTbNMqUxvQRAyrZzDsGYdLVL9zYtAWWM']);

            expect($result)->toBe($expectedResult);
        });

        it('handles RPC errors', function () {
            $this->mockHandler->append(
                new Response(200, [], json_encode([
                    'jsonrpc' => '2.0',
                    'error' => [
                        'code' => -32602,
                        'message' => 'Invalid params',
                        'data' => null,
                    ],
                    'id' => 1,
                ]))
            );

            expect(function () {
                $this->rpcClient->call('getBalance', ['invalid-pubkey']);
            })->toThrow(RpcException::class);
        });

        it('handles HTTP errors', function () {
            $this->mockHandler->append(
                new RequestException('Connection failed', new \GuzzleHttp\Psr7\Request('POST', '/'))
            );

            expect(function () {
                $this->rpcClient->call('getHealth');
            })->toThrow(RpcException::class);
        });
    });

    describe('Account Methods', function () {
        it('gets account info', function () {
            $pubkey = '9WzDXwBbmkg8ZTbNMqUxvQRAyrZzDsGYdLVL9zYtAWWM';
            $expectedResult = [
                'context' => ['slot' => 123456],
                'value' => [
                    'data' => ['', 'base64'],
                    'executable' => false,
                    'lamports' => 1000000000,
                    'owner' => '11111111111111111111111111111112',
                    'rentEpoch' => 361,
                ],
            ];

            $this->mockHandler->append(
                new Response(200, [], json_encode([
                    'jsonrpc' => '2.0',
                    'result' => $expectedResult,
                    'id' => 1,
                ]))
            );

            $result = $this->rpcClient->getAccountInfo($pubkey);
            expect($result)->toBe($expectedResult);
        });

        it('gets multiple accounts', function () {
            $pubkeys = [
                '9WzDXwBbmkg8ZTbNMqUxvQRAyrZzDsGYdLVL9zYtAWWM',
                'FEy4VtMZVo8BVwfkTTXHBdw4MZLL6WrhNpnNb8QoFeVt',
            ];

            $expectedResult = [
                'context' => ['slot' => 123456],
                'value' => [
                    ['lamports' => 1000000000, 'owner' => '11111111111111111111111111111112'],
                    ['lamports' => 2000000000, 'owner' => '11111111111111111111111111111112'],
                ],
            ];

            $this->mockHandler->append(
                new Response(200, [], json_encode([
                    'jsonrpc' => '2.0',
                    'result' => $expectedResult,
                    'id' => 1,
                ]))
            );

            $result = $this->rpcClient->getMultipleAccounts($pubkeys);
            expect($result)->toBe($expectedResult);
        });

        it('gets account balance', function () {
            $pubkey = '9WzDXwBbmkg8ZTbNMqUxvQRAyrZzDsGYdLVL9zYtAWWM';
            $expectedResult = [
                'context' => ['slot' => 123456],
                'value' => 1000000000,
            ];

            $this->mockHandler->append(
                new Response(200, [], json_encode([
                    'jsonrpc' => '2.0',
                    'result' => $expectedResult,
                    'id' => 1,
                ]))
            );

            $result = $this->rpcClient->getBalance($pubkey);
            expect($result)->toBe($expectedResult);
        });
    });

    describe('Block Methods', function () {
        it('gets block height', function () {
            $expectedResult = 123456789;

            $this->mockHandler->append(
                new Response(200, [], json_encode([
                    'jsonrpc' => '2.0',
                    'result' => $expectedResult,
                    'id' => 1,
                ]))
            );

            $result = $this->rpcClient->getBlockHeight();
            expect($result)->toBe($expectedResult);
        });

        it('gets block', function () {
            $slot = 123456;
            $expectedResult = [
                'blockHeight' => 123456,
                'blockTime' => 1640995200,
                'blockhash' => '3Eq21vXNB5s86c62bVuUfTeaMif1N2kUqRPBmGRJhyTA',
                'parentSlot' => 123455,
                'previousBlockhash' => '9J7x6vBZtHt7fLCe7bRBFWVnVqYR4WLm5zHN8xt8f2Ks',
            ];

            $this->mockHandler->append(
                new Response(200, [], json_encode([
                    'jsonrpc' => '2.0',
                    'result' => $expectedResult,
                    'id' => 1,
                ]))
            );

            $result = $this->rpcClient->getBlock($slot);
            expect($result)->toBe($expectedResult);
        });

        it('gets blocks with limit', function () {
            $startSlot = 123450;
            $limit = 5;
            $expectedResult = [123451, 123452, 123453, 123454, 123455];

            $this->mockHandler->append(
                new Response(200, [], json_encode([
                    'jsonrpc' => '2.0',
                    'result' => $expectedResult,
                    'id' => 1,
                ]))
            );

            $result = $this->rpcClient->getBlocksWithLimit($startSlot, $limit);
            expect($result)->toBe($expectedResult);
        });
    });

    describe('Transaction Methods', function () {
        it('gets transaction', function () {
            $signature = '3Eq21vXNB5s86c62bVuUfTeaMif1N2kUqRPBmGRJhyTA';
            $expectedResult = [
                'slot' => 123456,
                'blockTime' => 1640995200,
                'transaction' => [
                    'message' => ['accountKeys' => [], 'instructions' => []],
                    'signatures' => [$signature],
                ],
            ];

            $this->mockHandler->append(
                new Response(200, [], json_encode([
                    'jsonrpc' => '2.0',
                    'result' => $expectedResult,
                    'id' => 1,
                ]))
            );

            $result = $this->rpcClient->getTransaction($signature);
            expect($result)->toBe($expectedResult);
        });

        it('gets signature statuses', function () {
            $signatures = ['3Eq21vXNB5s86c62bVuUfTeaMif1N2kUqRPBmGRJhyTA'];
            $expectedResult = [
                'context' => ['slot' => 123456],
                'value' => [
                    [
                        'slot' => 123456,
                        'confirmations' => 10,
                        'err' => null,
                        'confirmationStatus' => 'confirmed',
                    ],
                ],
            ];

            $this->mockHandler->append(
                new Response(200, [], json_encode([
                    'jsonrpc' => '2.0',
                    'result' => $expectedResult,
                    'id' => 1,
                ]))
            );

            $result = $this->rpcClient->getSignatureStatuses($signatures);
            expect($result)->toBe($expectedResult);
        });

        it('sends transaction', function () {
            $transaction = 'base64-encoded-transaction';
            $expectedResult = '3Eq21vXNB5s86c62bVuUfTeaMif1N2kUqRPBmGRJhyTA';

            $this->mockHandler->append(
                new Response(200, [], json_encode([
                    'jsonrpc' => '2.0',
                    'result' => $expectedResult,
                    'id' => 1,
                ]))
            );

            $result = $this->rpcClient->sendTransaction($transaction);
            expect($result)->toBe($expectedResult);
        });

        it('simulates transaction', function () {
            $transaction = 'base64-encoded-transaction';
            $expectedResult = [
                'context' => ['slot' => 123456],
                'value' => [
                    'err' => null,
                    'logs' => ['Program log: Hello, world!'],
                    'accounts' => null,
                    'unitsConsumed' => 200,
                ],
            ];

            $this->mockHandler->append(
                new Response(200, [], json_encode([
                    'jsonrpc' => '2.0',
                    'result' => $expectedResult,
                    'id' => 1,
                ]))
            );

            $result = $this->rpcClient->simulateTransaction($transaction);
            expect($result)->toBe($expectedResult);
        });
    });

    describe('Network Methods', function () {
        it('gets health status', function () {
            $expectedResult = 'ok';

            $this->mockHandler->append(
                new Response(200, [], json_encode([
                    'jsonrpc' => '2.0',
                    'result' => $expectedResult,
                    'id' => 1,
                ]))
            );

            $result = $this->rpcClient->getHealth();
            expect($result)->toBe($expectedResult);
        });

        it('gets version', function () {
            $expectedResult = [
                'solana-core' => '1.14.17',
                'feature-set' => 4081031270,
            ];

            $this->mockHandler->append(
                new Response(200, [], json_encode([
                    'jsonrpc' => '2.0',
                    'result' => $expectedResult,
                    'id' => 1,
                ]))
            );

            $result = $this->rpcClient->getVersion();
            expect($result)->toBe($expectedResult);
        });

        it('gets epoch info', function () {
            $expectedResult = [
                'absoluteSlot' => 123456789,
                'blockHeight' => 123456,
                'epoch' => 300,
                'slotIndex' => 1234,
                'slotsInEpoch' => 432000,
                'transactionCount' => 987654321,
            ];

            $this->mockHandler->append(
                new Response(200, [], json_encode([
                    'jsonrpc' => '2.0',
                    'result' => $expectedResult,
                    'id' => 1,
                ]))
            );

            $result = $this->rpcClient->getEpochInfo();
            expect($result)->toBe($expectedResult);
        });
    });

    describe('Token Methods', function () {
        it('gets token account balance', function () {
            $tokenAccount = 'TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA';
            $expectedResult = [
                'context' => ['slot' => 123456],
                'value' => [
                    'amount' => '1000000000',
                    'decimals' => 9,
                    'uiAmount' => 1,
                    'uiAmountString' => '1',
                ],
            ];

            $this->mockHandler->append(
                new Response(200, [], json_encode([
                    'jsonrpc' => '2.0',
                    'result' => $expectedResult,
                    'id' => 1,
                ]))
            );

            $result = $this->rpcClient->getTokenAccountBalance($tokenAccount);
            expect($result)->toBe($expectedResult);
        });

        it('gets token accounts by owner', function () {
            $owner = '9WzDXwBbmkg8ZTbNMqUxvQRAyrZzDsGYdLVL9zYtAWWM';
            $filter = ['programId' => 'TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA'];

            $expectedResult = [
                'context' => ['slot' => 123456],
                'value' => [
                    [
                        'account' => [
                            'data' => ['', 'base64'],
                            'executable' => false,
                            'lamports' => 2039280,
                            'owner' => 'TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA',
                        ],
                        'pubkey' => 'TokenAccountPublicKey',
                    ],
                ],
            ];

            $this->mockHandler->append(
                new Response(200, [], json_encode([
                    'jsonrpc' => '2.0',
                    'result' => $expectedResult,
                    'id' => 1,
                ]))
            );

            $result = $this->rpcClient->getTokenAccountsByOwner($owner, $filter);
            expect($result)->toBe($expectedResult);
        });

        it('gets token supply', function () {
            $tokenMint = 'So11111111111111111111111111111111111111112';
            $expectedResult = [
                'context' => ['slot' => 123456],
                'value' => [
                    'amount' => '1000000000000000000',
                    'decimals' => 9,
                    'uiAmount' => 1000000000,
                    'uiAmountString' => '1000000000',
                ],
            ];

            $this->mockHandler->append(
                new Response(200, [], json_encode([
                    'jsonrpc' => '2.0',
                    'result' => $expectedResult,
                    'id' => 1,
                ]))
            );

            $result = $this->rpcClient->getTokenSupply($tokenMint);
            expect($result)->toBe($expectedResult);
        });
    });

    describe('Utility Methods', function () {
        it('converts lamports to SOL', function () {
            $lamports = 1000000000; // 1 SOL
            $sol = $this->rpcClient->lamportsToSol($lamports);
            expect($sol)->toBe(1.0);

            $lamports = 500000000; // 0.5 SOL
            $sol = $this->rpcClient->lamportsToSol($lamports);
            expect($sol)->toBe(0.5);
        });

        it('converts SOL to lamports', function () {
            $sol = 1.0;
            $lamports = $this->rpcClient->solToLamports($sol);
            expect($lamports)->toBe(1000000000);

            $sol = 0.5;
            $lamports = $this->rpcClient->solToLamports($sol);
            expect($lamports)->toBe(500000000);
        });

        it('sets and gets endpoint', function () {
            $newEndpoint = 'https://api.mainnet-beta.solana.com';
            $this->rpcClient->setEndpoint($newEndpoint);
            expect($this->rpcClient->getEndpoint())->toBe($newEndpoint);
        });

        it('sets cache configuration', function () {
            $this->rpcClient->setCache(false);
            $this->rpcClient->setCacheTimeout(600);
            // No exception should be thrown
            expect(true)->toBe(true);
        });
    });

    describe('Request Airdrop (Testnet Only)', function () {
        it('requests airdrop', function () {
            $pubkey = '9WzDXwBbmkg8ZTbNMqUxvQRAyrZzDsGYdLVL9zYtAWWM';
            $lamports = 1000000000; // 1 SOL
            $expectedResult = '3Eq21vXNB5s86c62bVuUfTeaMif1N2kUqRPBmGRJhyTA';

            $this->mockHandler->append(
                new Response(200, [], json_encode([
                    'jsonrpc' => '2.0',
                    'result' => $expectedResult,
                    'id' => 1,
                ]))
            );

            $result = $this->rpcClient->requestAirdrop($pubkey, $lamports);
            expect($result)->toBe($expectedResult);
        });
    });
});
