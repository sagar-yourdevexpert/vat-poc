<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Services\ZatcaApiService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Saleh7\Zatca\{
    SignatureInformation,UBLDocumentSignatures,ExtensionContent,UBLExtension,UBLExtensions,Signature,InvoiceType,AdditionalDocumentReference,
    TaxScheme,PartyTaxScheme,Address,LegalEntity,Delivery,Party,PaymentMeans,TaxCategory,
    AllowanceCharge,TaxSubTotal,TaxTotal,LegalMonetaryTotal,ClassifiedTaxCategory,Item,Price,InvoiceLine,
    GeneratorInvoice,Invoice,UnitCode,OrderReference,BillingReference,Contract,Attachment
};
use Saleh7\Zatca\CertificateBuilder;


class ZatcaController extends Controller
    /**
     * Generate a ZATCA-compliant invoice XML from dynamic request data.
     *
     * POST /api/zatca/generate-invoice
     *
     * Expected JSON payload:
     * {
     *   "supplier": { ... },
     *   "customer": { ... },
     *   "invoice": { ... },
     *   "lines": [ ... ]
     * }
     */
    {
        public function generateInvoice(Request $request)
    {
        $data = $request->validate([
            'supplier.name' => 'required|string',
            'supplier.vat_number' => 'required|string',
            'supplier.address' => 'required|string',
            'supplier.building_no' => 'required|string',
            'supplier.postal_code' => 'required|string',
            'supplier.city' => 'required|string',
            'supplier.country_code' => 'required|string',
            'customer.name' => 'required|string',
            'customer.vat_number' => 'required|string',
            'customer.address' => 'required|string',
            'customer.building_no' => 'required|string',
            'customer.postal_code' => 'required|string',
            'customer.city' => 'required|string',
            'customer.country_code' => 'required|string',
            'invoice.invoice_number' => 'required|string',
            'invoice.issue_date' => 'required|date',
            'invoice.type' => 'required|string',
            'invoice.type_code' => 'required|string',
            'invoice.currency' => 'required|string',
            'invoice.taxable_amount' => 'required|numeric',
            'invoice.tax_amount' => 'required|numeric',
            'invoice.line_extension_amount' => 'required|numeric',
            'invoice.tax_exclusive_amount' => 'required|numeric',
            'invoice.tax_inclusive_amount' => 'required|numeric',
            'invoice.prepaid_amount' => 'required|numeric',
            'invoice.payable_amount' => 'required|numeric',
            'invoice.allowance_total_amount' => 'required|numeric',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string',
            'lines.*.quantity' => 'required|numeric',
            'lines.*.unit_price' => 'required|numeric',
            'lines.*.vat_rate' => 'required|numeric',
            'lines.*.tax_amount' => 'required|numeric',
            'lines.*.total' => 'required|numeric',
        ]);

        // Supplier
        $supplierTaxScheme = (new PartyTaxScheme())
            ->setCompanyId($data['supplier']['vat_number'])
            ->setTaxScheme((new TaxScheme())->setId('VAT'));
        $supplierAddress = (new Address())
            ->setStreetName($data['supplier']['address'])
            ->setBuildingNumber($data['supplier']['building_no'])
            ->setCityName($data['supplier']['city'])
            ->setPostalZone($data['supplier']['postal_code'])
            ->setCountry($data['supplier']['country_code']);
        $supplierLegalEntity = (new LegalEntity())
            ->setRegistrationName($data['supplier']['name']);
        $supplierParty = (new Party())
            ->setPartyIdentification($data['supplier']['vat_number'])
            ->setPartyIdentificationId('CRN')
            ->setLegalEntity($supplierLegalEntity)
            ->setPartyTaxScheme($supplierTaxScheme)
            ->setPostalAddress($supplierAddress);

        // Customer
        $customerTaxScheme = (new PartyTaxScheme())
            ->setCompanyId($data['customer']['vat_number'])
            ->setTaxScheme((new TaxScheme())->setId('VAT'));
        $customerAddress = (new Address())
            ->setStreetName($data['customer']['address'])
            ->setBuildingNumber($data['customer']['building_no'])
            ->setCityName($data['customer']['city'])
            ->setPostalZone($data['customer']['postal_code'])
            ->setCountry($data['customer']['country_code']);
        $customerLegalEntity = (new LegalEntity())
            ->setRegistrationName($data['customer']['name']);
        $customerParty = (new Party())
            ->setPartyIdentification($data['customer']['vat_number'])
            ->setPartyIdentificationId('CRN')
            ->setLegalEntity($customerLegalEntity)
            ->setPartyTaxScheme($customerTaxScheme)
            ->setPostalAddress($customerAddress);

        // Invoice Lines
        $invoiceLines = [];
        foreach ($data['lines'] as $idx => $line) {
            $classifiedTax = (new ClassifiedTaxCategory())
                ->setPercent($line['vat_rate'])
                ->setTaxScheme((new TaxScheme())->setId('VAT'));
            $item = (new Item())
                ->setName($line['description'])
                ->setClassifiedTaxCategory($classifiedTax);
            $price = (new Price())
                ->setUnitCode(UnitCode::UNIT)
                ->setPriceAmount($line['unit_price']);
            $lineTaxTotal = (new TaxTotal())
                ->setTaxAmount($line['tax_amount']);
            $invoiceLine = (new InvoiceLine())
                ->setUnitCode('PCE')
                ->setId($idx + 1)
                ->setItem($item)
                ->setLineExtensionAmount($line['total'])
                ->setPrice($price)
                ->setTaxTotal($lineTaxTotal)
                ->setInvoicedQuantity($line['quantity']);
            $invoiceLines[] = $invoiceLine;
        }

        // Additional Document References (optional, can be expanded)
        $additionalDocs = [];
        $additionalDocs[] = (new AdditionalDocumentReference())
            ->setId('ICV')
            ->setUUID($data['invoice']['invoice_number']);
        $additionalDocs[] = (new AdditionalDocumentReference())
            ->setId('QR');

        // Tax Category and Totals
        $taxCategory = (new TaxCategory())
            ->setPercent($data['lines'][0]['vat_rate'])
            ->setTaxScheme((new TaxScheme())->setId('VAT'));
        $taxSubTotal = (new TaxSubTotal())
            ->setTaxableAmount($data['invoice']['taxable_amount'])
            ->setTaxAmount($data['invoice']['tax_amount'])
            ->setTaxCategory($taxCategory);
        $taxTotal = (new TaxTotal())
            ->addTaxSubTotal($taxSubTotal)
            ->setTaxAmount($data['invoice']['tax_amount']);

        // Legal Monetary Total
        $legalMonetaryTotal = (new LegalMonetaryTotal())
            ->setLineExtensionAmount($data['invoice']['line_extension_amount'])
            ->setTaxExclusiveAmount($data['invoice']['tax_exclusive_amount'])
            ->setTaxInclusiveAmount($data['invoice']['tax_inclusive_amount'])
            ->setPrepaidAmount($data['invoice']['prepaid_amount'])
            ->setPayableAmount($data['invoice']['payable_amount'])
            ->setAllowanceTotalAmount($data['invoice']['allowance_total_amount']);

        // Invoice Type
        $invoiceType = (new InvoiceType())
            ->setInvoice($data['invoice']['type'])
            ->setInvoiceType($data['invoice']['type_code']);

        // Invoice
        $invoice = (new Invoice())
            ->setId($data['invoice']['invoice_number'])
            ->setIssueDate(new \DateTime($data['invoice']['issue_date']))
            ->setIssueTime(new \DateTime(date('H:i:s')))
            ->setInvoiceType($invoiceType)
            ->setInvoiceCurrencyCode($data['invoice']['currency'])
            ->setAccountingSupplierParty($supplierParty)
            ->setAccountingCustomerParty($customerParty)
            ->setTaxTotal($taxTotal)
            ->setLegalMonetaryTotal($legalMonetaryTotal)
            ->setInvoiceLines($invoiceLines)
            ->setAdditionalDocumentReferences($additionalDocs);

        // Generate XML
        $xmlResult = GeneratorInvoice::invoice($invoice)->saveXMLFile(storage_path('app/invoices/invoice_' . $data['invoice']['invoice_number'] . '.xml'), true);
        if (is_string($xmlResult)) {
            $xmlOutput = $xmlResult;
        } elseif (is_object($xmlResult) && method_exists($xmlResult, 'getXml')) {
            $xmlOutput = $xmlResult->getXml();
        } else {
            $xmlOutput = null;
        }
        if (!$xmlOutput) {
            Log::error('ZATCA XML generation failed', ['xmlResult' => $xmlResult, 'invoice' => $invoice]);
            return response()->json(['error' => 'Failed to generate XML.'], 500);
        }

        return response($xmlOutput, 200)->header('Content-Type', 'application/xml');
    }
    // ...existing code...

    // Insert the generateInvoice method here (already added in previous patch)
    // ...existing code...

    /**
     * TEMP: Run the working standalone ZATCA example inside Laravel to isolate environment issues.
     */
    public function testStandalone()
    {
        // ...existing code for testStandalone...
    }

    /**
     * Generate a Certificate Signing Request (CSR) for ZATCA.
     *
     * Required fields:
     * - organization_identifier (15 digits, starts and ends with 3)
     * - solution_name
     * - model
     * - serial_number
     * - common_name
     * - country (2 chars)
     * - organization_name
     * - organizational_unit_name
     * - address
     * - invoice_type (int, 4 digits)
     * - production (bool)
     * - business_category
     */
    public function generateCsr(Request $request)
    {
        $data = $request->validate([
            'organization_identifier' => 'required|string|size:15',
            'solution_name' => 'required|string',
            'model' => 'required|string',
            'serial_number' => 'required|string',
            'common_name' => 'required|string',
            'organization_name' => 'required|string',
            'organizational_unit_name' => 'required|string',
            'address' => 'required|string',
            'invoice_type' => 'required|integer',
            'production' => 'required|boolean',
            'business_category' => 'required|string',
        ]);

        $builder = (new CertificateBuilder())
            ->setOrganizationIdentifier($data['organization_identifier'])
            ->setSerialNumber($data['solution_name'], $data['model'], $data['serial_number'])
            ->setCommonName($data['common_name'])
            ->setCountryName($data['country'])
            ->setOrganizationName($data['organization_name'])
            ->setOrganizationalUnitName($data['organizational_unit_name'])
            ->setAddress($data['address'])
            ->setInvoiceType($data['invoice_type'])
            ->setProduction($data['production'])
            ->setBusinessCategory($data['business_category']);

        $builder->generate();
        $csr = $builder->getCsr();

        // Save private key to a temp file and return contents
        $tmpKey = tempnam(sys_get_temp_dir(), 'zatca_key_');
        $builder->savePrivateKey($tmpKey);
        $privateKey = file_get_contents($tmpKey);
        unlink($tmpKey);

        return response()->json([
            'csr' => $csr,
            'private_key' => $privateKey,
        ]);
    }

    /**
     * Sign a ZATCA invoice XML (stub, expand as needed).
     */
    public function signInvoice(Request $request)
    {
        $data = $request->validate([
            'xml' => 'required|string',
        ]);

        // Example: sign XML (implement with your certificate)
        // $signedXml = signZatcaXml($data['xml']);
        $signedXml = $data['xml']; // Stub

        return response($signedXml, 200)->header('Content-Type', 'application/xml');
    }
}
