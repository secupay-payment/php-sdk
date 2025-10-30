<?php
/**
 * Secupay AG Php SDK
 *
 * This library allows to interact with the Secupay AG payment service.
 *
 * Copyright owner: Wallee AG
 * Website: https://secupay.com/en
 * Developer email: ecosystem-team@wallee.com
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Secupay\Sdk\Test;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Secupay\Sdk\Model\TransactionPending;
use Secupay\Sdk\Model\LineItem;
use Secupay\Sdk\Model\LineItemType;
use Secupay\Sdk\Model\TokenizationMode;
use Secupay\Sdk\Model\CustomersPresence;
use Secupay\Sdk\Model\TransactionCompletionState;
use Secupay\Sdk\Model\TransactionCompletionBehavior;
use Secupay\Sdk\Model\TransactionVoidState;
use Secupay\Sdk\Model\TokenCreate;
use Secupay\Sdk\Model\TerminalReceiptFormat;
use Secupay\Sdk\Model\TokenizedCardRequest;
use Secupay\Sdk\Model\TokenizedCardDataCreate;
use Secupay\Sdk\Model\CreationEntityState;
use Secupay\Sdk\Model\ChargeState;
use Secupay\Sdk\Model\TokenUpdate;
use Secupay\Sdk\Model\TransactionCompletionDetails;
use Secupay\Sdk\Model\Transaction;
use Secupay\Sdk\Model\TransactionState;
use Secupay\Sdk\Service\TransactionsService;
use Secupay\Sdk\Service\TokensService;
use Secupay\Sdk\ApiException;
use Secupay\Sdk\Test\Constants;
use Secupay\Sdk\Test\TestUtils;

/** API tests for Transactions Service */
class TransactionsServiceTest extends TestCase
{
    private static ?TransactionsService $transactionsService = null;
    private static ?TokensService $tokensService = null;

    private static string $integrationMode = "payment_page";

    public static function setUpBeforeClass(): void
    {
        $client = new Client();
        $configuration = Constants::getConfigurationInstance();
        self::$transactionsService = new TransactionsService(
            config: $configuration,
            client: $client
        );
        self::$tokensService = new TokensService(
            config: $configuration,
            client: $client
        );
    }

    /**
     * Creates a new transaction.
     *
     * <p>Verifies that: Transaction state is PENDING
     */
    public function testCreateAndFindPendingTransaction(): void
    {
        $transaction = self::create(TestUtils::getTransactionCreatePayload());

        $found = $this::$transactionsService->getPaymentTransactionsId(
            $transaction->getId(),
            Constants::$spaceId
        );

        $this::assertEquals(
            TransactionState::PENDING,
            $found->getState(),
            "Transaction state must be PENDING"
        );

        $this->assertEquals(
            $transaction->getMerchantReference(),
            $found->getMerchantReference(),
            "Merchant reference should match."
        );
    }

    /**
     * Confirms a pending transaction.
     *
     * <p>Verifies that:
     * <ul>
     *   <li>Transaction state is CONFIRMED
     *   <li>Transaction entity version is correctly incremented
     *   <li>Merchant reference is correctly updated
     * </ul>
     */
    public function testConfirmShouldMakeTransactionConfirmed(): void
    {
        $transactionCreate = TestUtils::getTransactionCreatePayload();
        $transactionCreate->setMerchantReference("Test Initial Confirm");
        $transaction = $this->create($transactionCreate);

        $transactionPending = (new TransactionPending())
            ->setVersion(2)
            ->setMerchantReference("Test Confirm");

        $confirmedTransaction = $this::$transactionsService->postPaymentTransactionsIdConfirm(
            $transaction->getId(),
            Constants::$spaceId,
            $transactionPending
        );

        $this::assertEquals(
            TransactionState::CONFIRMED,
            $confirmedTransaction->getState(),
            "Transaction state must be CONFIRMED"
        );

        $this->assertEquals(
            $transactionPending->getMerchantReference(),
            $confirmedTransaction->getMerchantReference(),
            "Merchant reference should match"
        );
    }


    /**
     * Processes a transaction via charge flow.
     *
     * <p>Verifies that: Transaction state is PROCESSING
     * <ul>
     *   <li>
     * </ul>
     */
    public function testProcessViaChargeFlowShouldMakeTransactionProcessing()
    {
        $transaction = $this->create(TestUtils::getTransactionCreatePayload());

        $processingTransaction = self::$transactionsService->postPaymentTransactionsIdChargeFlowApply(
            $transaction->getId(),
            Constants::$spaceId
        );

        $this->assertEquals(
            TransactionState::PROCESSING,
            $processingTransaction->getState(),
            "Transaction state must be PROCESSING"
        );
    }

    /**
     * Processes and cancels a transaction via charge flow.
     *
     * <p>Verifies that:
     * <ul>
     *   <li>Initially, transaction state is PROCESSING
     *   <li>After cancellation, transaction state is FAILED
     * </ul>
     */
    public function testCancelChargeFlowShouldMakeTransactionFailed()
    {
        $transaction = $this->create(TestUtils::getTransactionCreatePayload());

        $processingTransaction = self::$transactionsService->postPaymentTransactionsIdChargeFlowApply(
            $transaction->getId(),
            Constants::$spaceId
        );

        $this->assertEquals(
            TransactionState::PROCESSING,
            $processingTransaction->getState(),
            "Transaction state must be PROCESSING"
        );

        $failedTransaction = self::$transactionsService->postPaymentTransactionsIdChargeFlowCancel(
            $transaction->getId(),
            Constants::$spaceId
        );

        $this->assertEquals(
            TransactionState::FAILED,
            $failedTransaction->getState(),
            "Transaction state must be FAILED"
        );
    }

    /**
     * Processes a transaction and retrieves payment page URL.
     *
     * <p>Verifies that:
     * <ul>
     *   <li>Retrieved URL contains space ID
     *   <li>Retrieved URL contains transaction ID
     * </ul>
     */
    public function testFetchPaymentPageUrlShouldReturnValidUrl()
    {
        $transaction = $this->create(TestUtils::getTransactionCreatePayload());

        $processingTransaction = self::$transactionsService->postPaymentTransactionsIdChargeFlowApply(
            $transaction->getId(),
            Constants::$spaceId
        );

        $url = self::$transactionsService->getPaymentTransactionsIdPaymentPageUrl(
            $transaction->getId(),
            Constants::$spaceId
        );

        $this->assertStringContainsString('/s/' . Constants::$spaceId, $url, "Space id should be present in url");
        $this->assertStringContainsString('securityToken=', $url, "Security token should be present in url");
        $this->assertStringContainsString((string) $processingTransaction->getId(), $url, "Transaction id should be present in url");
    }

    /**
     * Processes a transaction via charge flow and retrieves payment page URL.
     *
     * <p>Verifies that:
     * <ul>
     *   <li>Transaction state is PROCESSING
     *   <li>Retrieved URL contains space ID
     *   <li>Retrieved URL contains transaction ID
     * </ul>
     */
    public function testFetchChargeFlowUrlShouldReturnValidUrl()
    {
        $transaction = $this->create(TestUtils::getTransactionCreatePayload());

        $processingTransaction = self::$transactionsService->postPaymentTransactionsIdChargeFlowApply(
            $transaction->getId(),
            Constants::$spaceId
        );

        $this->assertEquals(
            TransactionState::PROCESSING,
            $processingTransaction->getState(),
            "Transaction state must be PROCESSING"
        );

        $url = self::$transactionsService->getPaymentTransactionsIdChargeFlowPaymentPageUrl(
            $processingTransaction->getId(),
            Constants::$spaceId
        );

        $this->assertStringContainsString((string) Constants::$spaceId, $url, "Url must contain space id");
        $this->assertStringContainsString((string) $processingTransaction->getId(), $url, "Url must contain transaction id");
        $this->assertStringContainsString('securityToken=', $url, "Url must contain security token");
    }









    /**
     * Creates transaction token for a transaction.
     *
     * <p>Verifies that:
     * <ul>
     *   <li>Token contains space ID
     *   <li>Token contains transaction ID
     * </ul>
     */
    public function testCreateTransactionCredentialsShouldCreateTransactionToken()
    {
        $transaction = $this->create(TestUtils::getTransactionCreatePayload());

        $credentials = $this->getCredentials($transaction->getId());

        $this->assertTrue(
            str_starts_with($credentials, (string) Constants::$spaceId),
            "Transaction credentials token should have valid format"
        );

        $this->assertNotNull($transaction->getId());

        $this->assertStringContainsString(
            (string) $transaction->getId(),
            $credentials,
            "Transaction credentials token should contain transaction id"
        );
    }

    /**
     * Gets IFrame payment URL for transaction.
     *
     * <p>Verifies that:
     * <ul>
     *   <li>URL contains space ID
     *   <li>URL contains transaction ID
     * </ul>
     */
    public function testFetchIFrameUrlShouldReturnValidUrl()
    {
        $transaction = $this->create(TestUtils::getTransactionCreatePayload());

        $iFrameUrl = self::$transactionsService->getPaymentTransactionsIdIframeJavascriptUrl(
            $transaction->getId(),
            Constants::$spaceId
        );

        $this->assertStringContainsString(
            (string) Constants::$spaceId,
            $iFrameUrl,
            "IFrame JavaScript URL should contain space id"
        );

        $this->assertStringContainsString(
            (string) $transaction->getId(),
            $iFrameUrl,
            "IFrame JavaScript URL should contain transaction id"
        );
    }

    /**
     * Gets Lightbox payment URL for transaction.
     *
     * <p>Verifies that:
     * <ul>
     *   <li>URL contains space ID
     *   <li>URL contains transaction ID
     * </ul>
     */
    public function testFetchLightboxUrlShouldReturnValidUrl()
    {
        $transaction = $this->create(TestUtils::getTransactionCreatePayload());

        $lightboxJavascriptUrl = self::$transactionsService->getPaymentTransactionsIdLightboxJavascriptUrl(
            $transaction->getId(),
            Constants::$spaceId
        );

        $this->assertStringContainsString(
            (string) Constants::$spaceId,
            $lightboxJavascriptUrl,
            "Lightbox URL should contain space id"
        );

        $this->assertStringContainsString(
            (string) $transaction->getId(),
            $lightboxJavascriptUrl,
            "Lightbox URL should contain transaction id"
        );
    }



    /**
     * Creates transaction and gets payment methods configuration.
     *
     * <ul>
     *   <li>Creates transaction
     *   <li>Gets payment methods configuration
     *   <li>Verifies that: Payment methods are present
     * </ul>
     */
    public function testFetchPaymentMethodsByIdShouldReturnAvailablePaymentMethods()
    {
        $transaction = $this->create(TestUtils::getTransactionCreatePayload());

        $methods = self::$transactionsService->getPaymentTransactionsIdPaymentMethodConfigurations(
            $transaction->getId(),
            self::$integrationMode,
            Constants::$spaceId
        );

        $this->assertNotNull($methods->getData(), "The payment method list should be present");
        $this->assertNotEmpty($methods->getData(), "Payment methods should be configured for a given transaction in test space");
    }

    /**
     * Creates transaction and finds it by credentials.
     *
     * <ul>
     *   <li>Creates transaction and gets its credentials
     *   <li>Finds transaction by credentials
     *   <li>Verifies that: Transaction is present
     * </ul>
     */
    public function testFetchTransactionWithCredentialsShouldReturnTransaction()
    {
        $transaction = $this->create(TestUtils::getTransactionCreatePayload());

        $credentials = self::$transactionsService->getPaymentTransactionsIdCredentials(
            $transaction->getId(),
            Constants::$spaceId
        );

        $retrieved = self::$transactionsService->getPaymentTransactionsByCredentialsCredentials(
            $credentials,
            Constants::$spaceId
        );

        $this->assertNotNull($retrieved, "Transaction must be present");
    }

    /**
     * Creates transaction and gets payment methods configuration by credentials.
     *
     * <ul>
     *   <li>Creates transaction and gets its credentials
     *   <li>Gets payment methods configuration by credentials
     *   <li>Verifies that: Payment methods are present
     * </ul>
     */
    public function testFetchPaymentMethodsWithCredentialsShouldReturnAvailablePaymentMethods()
    {
        $transaction = $this->create(TestUtils::getTransactionCreatePayload());

        $credentials = $this->getCredentials($transaction->getId());

        $methods = self::$transactionsService
            ->getPaymentTransactionsByCredentialsCredentialsPaymentMethodConfigurations(
                $credentials,
                self::$integrationMode,
                Constants::$spaceId
            );

        $this->assertNotNull($methods->getData(), "The payment method list should be present.");
        $this->assertNotEmpty($methods->getData(), "Payment methods should be configured for a given transaction in test space");
    }

    /**
     * Creates and exports a transaction.
     *
     * <ul>
     *   <li>Creates transaction, exports it
     *   <li>Verifies that: Export file exists
     * </ul>
     */
    public function testExportTransactionsShouldReturnFile()
    {
        $transaction = $this->create(TestUtils::getTransactionCreatePayload());

        $this->assertNotNull($transaction->getId());

        $fields = ['id'];

        $export = self::$transactionsService->getPaymentTransactionsExport(
            Constants::$spaceId,
            $fields,
            1,
            0,
            'id:ASC',
            'id:' . $transaction->getId()
        );

        $this->assertTrue(file_exists($export->getPathname()), "Export file should exist");
    }

    /**
     * Gets transaction by invalid credentials.
     *
     * <ul>
     *   <li>Attempts to retrieve a transaction using invalid credentials
     *   <li>Verifies that: Operation fails as expected
     * </ul>
     */
    public function testFetchWithCredentialsWithBadCredentialsShouldFail()
    {
        $this->expectException(ApiException::class, "Bad token should produce error response");

        self::$transactionsService->getPaymentTransactionsByCredentialsCredentials(
            'bad_credentials',
            Constants::$spaceId
        );
    }

    /**
     * Creates and updates a transaction.
     *
     * <ul>
     *   <li>Creates a new transaction
     *   <li>Updates it with new data
     *   <li>Verifies that:
     *       <ul>
     *         <li>Update was successful
     *         <li>Version was incremented correctly
     *       </ul>
     * </ul>
     */
    public function testUpdateShouldChangeTransactionData()
    {
        $transaction = $this->create(TestUtils::getTransactionCreatePayload());

        $update = new TransactionPending();
        $update->setLanguage('en-GB');
        $update->setVersion(2);

        $updated = self::$transactionsService->patchPaymentTransactionsId(
            $transaction->getId(),
            Constants::$spaceId,
            $update
        );

        $this->assertEquals('en-GB', $updated->getLanguage());
        $this->assertEquals($transaction->getMerchantReference(), $updated->getMerchantReference(), "Merchant reference should match.");
        $this->assertEquals(2, $updated->getVersion(), "Version should match");
    }



    /**
     * Verifies non-interactive transaction processing.
     *
     * <ul>
     *   <li>Processes a transaction without user interaction
     *   <li>Verifies that:
     *       <ul>
     *         <li>Transaction reaches the AUTHORIZED state
     *       </ul>
     * </ul>
     */
    public function testProcessWithoutUserInteractionShouldProcessTransactionProperly(): void
    {
        $transaction = $this->create(TestUtils::getTransactionCreatePayload());

        $processed = self::$transactionsService->postPaymentTransactionsIdProcessWithoutInteraction($transaction->getId(), Constants::$spaceId);

        $this->assertEquals($transaction->getId(), $processed->getId(), "Transaction ids must match");
        $this->assertEquals(TransactionState::PROCESSING, $processed->getState(), "Transaction state should be PROCESSING");
    }

    /**
     * Retrieves tokens by transaction credentials.
     *
     * <ul>
     *   <li>Creates a new transaction
     *   <li>Attempts to retrieve one-click tokens
     *   <li>Verifies that:
     *       <ul>
     *         <li>Response data is present
     *       </ul>
     * </ul>
     */
    public function testFetchOneClickTokenShouldReturnResponseWithoutException(): void
    {
        $transaction = $this->create(TestUtils::getTransactionCreatePayload());

        $credentials = $this->getCredentials($transaction->getId());

        $tokens = self::$transactionsService->getPaymentTransactionsByCredentialsCredentialsOneClickTokens($credentials, Constants::$spaceId);

        $this->assertNotNull($tokens);
        $this->assertNotNull($tokens->getData(), "Token data should not be null");
    }


    /**
     * Gets mobile sdk url by credentials
     *
     * <ul>
     *   <li>Creates a new transaction
     *   <li>Gets mobile sdk url
     *   <li>Verifies that:
     *       <ul>
     *         <li>Returned url contains space id
     *         <li>Returned url contains securityToken
     *       </ul>
     * </ul>
     */
    public function testFetchMobileSdkUrlByCredentialsShouldReturnValidUrl(): void
    {
        $transaction = $this->create(TestUtils::getTransactionCreatePayload());

        $credentials = $this->getCredentials($transaction->getId());

        $url = self::$transactionsService->getPaymentTransactionsByCredentialsCredentialsMobileSdkUrl($credentials, Constants::$spaceId);

        $this->assertStringContainsString("/s/" . Constants::$spaceId, $url, "Space id should be present in url");
        $this->assertStringContainsString("securityToken=", $url, "Security token should be present in url");
    }


    /**
     * Updates charge flow recipient for processing transaction
     * <li>Verifies that:
     *     <ul>
     *        <li>Operation made without exceptions
     *     </ul>
     */
    public function testUpdateChargeFlowRecipientShouldNotThrow(): void
    {
        $transaction = $this->create(TestUtils::getTransactionCreatePayload());

        $processingTransaction = self::$transactionsService->postPaymentTransactionsIdChargeFlowApply($transaction->getId(), Constants::$spaceId);

        $this->expectNotToPerformAssertions();
        self::$transactionsService->postPaymentTransactionsIdChargeFlowUpdateRecipient($transaction->getId(), 1453447675844, "test2@domain.com", Constants::$spaceId);
    }


    private function create($transactionCreate): Transaction
    {
        return self::$transactionsService->postPaymentTransactions(
            Constants::$spaceId,
            $transactionCreate
        );
    }

    private function getCredentials($transactionId): string
    {
        return $this::$transactionsService->getPaymentTransactionsIdCredentials(
            $transactionId,
            Constants::$spaceId
        );
    }
}