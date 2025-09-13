<?php
require __DIR__ . '/vendor/autoload.php';

use Saleh7\Zatca\{
    TaxScheme, PartyTaxScheme, Address, LegalEntity, Party, ClassifiedTaxCategory, Item, Price, TaxTotal, InvoiceLine, AdditionalDocumentReference, TaxSubTotal, LegalMonetaryTotal, InvoiceType, Invoice, GeneratorInvoice
};

// --- Hardcoded Example ---
$supplierTaxScheme = (new PartyTaxScheme())
    ->setCompanyId('311111111101113')
    ->setTaxScheme((new TaxScheme())->setId('VAT'));
$customerTaxScheme = (new PartyTaxScheme())
    ->setCompanyId('312222222201113')
    ->setTaxScheme((new TaxScheme())->setId('VAT'));
$supplierAddress = (new Address())
    ->setStreetName('123 Main St')
    ->setBuildingNumber('1')
    ->setCityName('Riyadh')
    ->setPostalZone('12345')
    ->setCountry('SA');
$customerAddress = (new Address())
    ->setStreetName('456 Elm St')
    ->setBuildingNumber('2')
    ->setCityName('Jeddah')
    ->setPostalZone('54321')
    ->setCountry('SA');
$supplierLegalEntity = (new LegalEntity())
    ->setRegistrationName('Your Company');
$customerLegalEntity = (new LegalEntity())
    ->setRegistrationName('Customer Name');
$supplierParty = (new Party())
    ->setPartyIdentification('311111111101113')
    ->setPartyIdentificationId('CRN')
    ->setLegalEntity($supplierLegalEntity)
    ->setPartyTaxScheme($supplierTaxScheme)
    ->setPostalAddress($supplierAddress);
$customerParty = (new Party())
    ->setPartyIdentification('312222222201113')
    ->setPartyIdentificationId('CRN')
    ->setLegalEntity($customerLegalEntity)
    ->setPartyTaxScheme($customerTaxScheme)
    ->setPostalAddress($customerAddress);
$classifiedTax = (new ClassifiedTaxCategory())
    ->setPercent(15)
    ->setTaxScheme((new TaxScheme())->setId('VAT'));
$taxCategory = (new \Saleh7\Zatca\TaxCategory())
    ->setPercent(15)
    ->setTaxScheme((new TaxScheme())->setId('VAT'));
$item = (new Item())
    ->setName('Product A')
    ->setClassifiedTaxCategory($classifiedTax);
$price = (new Price())
    ->setUnitCode(\Saleh7\Zatca\UnitCode::UNIT)
    ->setPriceAmount(50.0);
$lineTaxTotal = (new TaxTotal())
    ->setTaxAmount(15.0)
    ->setRoundingAmount(100.0);
$invoiceLine = (new InvoiceLine())
    ->setUnitCode('PCE')
    ->setId(1)
    ->setItem($item)
    ->setLineExtensionAmount(100.0)
    ->setPrice($price)
    ->setTaxTotal($lineTaxTotal)
    ->setInvoicedQuantity(2.0);
$invoiceLines = [$invoiceLine];
$additionalDocs = [];
$additionalDocs[] = (new AdditionalDocumentReference())
    ->setId('ICV')
    ->setUUID('INV-001');
$additionalDocs[] = (new AdditionalDocumentReference())
    ->setId('QR');
$taxSubTotal = (new TaxSubTotal())
    ->setTaxableAmount(100.0)
    ->setTaxAmount(15.0)
    ->setTaxCategory($taxCategory);
$taxTotal = (new TaxTotal())
    ->addTaxSubTotal($taxSubTotal)
    ->setTaxAmount(15.0);
$legalMonetaryTotal = (new LegalMonetaryTotal())
    ->setLineExtensionAmount(100.0)
    ->setTaxExclusiveAmount(100.0)
    ->setTaxInclusiveAmount(115.0)
    ->setPrepaidAmount(0.0)
    ->setPayableAmount(115.0)
    ->setAllowanceTotalAmount(0.0);
$invoiceType = (new InvoiceType())
    ->setInvoice('standard')
    ->setInvoiceType('invoice');
$invoice = (new Invoice())
    ->setId('INV-001')
    ->setIssueDate(new DateTime('2025-09-13'))
    ->setIssueTime(new DateTime('14:32:00'))
    ->setInvoiceType($invoiceType)
    ->setInvoiceCurrencyCode('SAR')
    ->setAccountingSupplierParty($supplierParty)
    ->setAccountingCustomerParty($customerParty)
    ->setTaxTotal($taxTotal)
    ->setLegalMonetaryTotal($legalMonetaryTotal)
    ->setInvoiceLines($invoiceLines)
    ->setAdditionalDocumentReferences($additionalDocs);

$xmlResult = GeneratorInvoice::invoice($invoice)->saveXMLFile(__DIR__ . '/invoice_INV-001.xml', true);
if (is_string($xmlResult)) {
    echo "Invoice XML generated successfully.\n";
    echo $xmlResult;
} elseif (is_object($xmlResult) && method_exists($xmlResult, '__toString')) {
    echo "Invoice XML generated successfully (object).\n";
    echo (string)$xmlResult;
} else {
    echo "Failed to generate invoice XML.\n";
    var_dump($xmlResult);
}
