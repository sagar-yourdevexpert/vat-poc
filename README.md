
## ZATCA VAT Integration API

This project provides APIs for Saudi ZATCA e-invoicing (Fatoora) integration using the php-zatca-xml library.

### API Flow

1. **Generate CSR** (`/api/zatca/generate-csr`)
	 - Use this endpoint first to generate a Certificate Signing Request (CSR) and private key.
	 - Submit the CSR to ZATCA to obtain your production certificate.

2. **Generate Invoice** (`/api/zatca/generate-invoice`)
	 - Use this after onboarding and obtaining your ZATCA certificate.
	 - Generates a ZATCA-compliant invoice XML.

3. **Sign Invoice** (`/api/zatca/sign-invoice`)
	 - Use this after generating the invoice XML.
	 - Digitally signs the invoice XML with your ZATCA-issued certificate/private key.

4. **Report/Clear Invoice to ZATCA** (`/api/zatca/report-invoice`)
	 - Use this to report or clear an invoice with ZATCA.
	 - Sends the signed invoice XML to ZATCA's compliance API.

#### Typical Flow

1. Call `generate-csr` → submit CSR to ZATCA → get certificate.
2. Call `generate-invoice` to create invoice XML.
3. Call `sign-invoice` to sign the invoice XML.
4. Call `report-invoice` to report the invoice to ZATCA.

---

## Usage: Two Integration Parts

### Part 1: Local Invoice Generation (Mandatory)
- Generate CSR, generate invoice XML, and sign invoice XML.
- This is required for all ZATCA-compliant e-invoicing solutions.
- You can use the generated and signed XML for archiving, printing, or sharing with customers.

### Part 2: Uploading/Reporting to ZATCA (Optional for some businesses)
- Uploading (reporting/clearance) to ZATCA via API is only required for Phase 2 (Integration Phase) and for certain business sizes/types.
- If your business is not required to report/clear invoices in real-time, you can skip the `/api/zatca/report-invoice` step.
- Check ZATCA regulations to see if you must upload invoices, or consult your compliance team.

---

### API Usage & Payloads

#### 1. Generate CSR

**POST** `/api/zatca/generate-csr`

**Payload:**
```json
{
	"organization_identifier": "312345678901233",
	"solution_name": "YourSolution",
	"model": "ModelX",
	"serial_number": "SN123456",
	"common_name": "Your Company",
	"country": "SA",
	"organization_name": "Your Company",
	"organizational_unit_name": "IT",
	"address": "123 Main St, Riyadh",
	"invoice_type": 1100,
	"production": false,
	"business_category": "Technology"
}
```
**Response:**
```json
{
	"csr": "-----BEGIN CERTIFICATE REQUEST-----...",
	"private_key": "-----BEGIN PRIVATE KEY-----..."
}
```

---

#### 2. Generate Invoice

**POST** `/api/zatca/generate-invoice`

**Payload:**
```json
{
	"supplier": {
		"name": "Your Company",
		"vat_number": "311111111101113",
		"address": "123 Main St",
		"building_no": "1",
		"postal_code": "12345",
		"city": "Riyadh",
		"country_code": "SA"
	},
	"customer": {
		"name": "Customer Name",
		"vat_number": "312222222201113",
		"address": "456 Elm St",
		"building_no": "2",
		"postal_code": "54321",
		"city": "Jeddah",
		"country_code": "SA"
	},
	"invoice": {
		"invoice_number": "INV-001",
		"issue_date": "2025-09-13",
		"type": "standard",
		"type_code": "invoice",
		"currency": "SAR",
		"taxable_amount": 100,
		"tax_amount": 15,
		"line_extension_amount": 100,
		"tax_exclusive_amount": 100,
		"tax_inclusive_amount": 115,
		"prepaid_amount": 0,
		"payable_amount": 115,
		"allowance_total_amount": 0
	},
	"lines": [
		{
			"description": "Product A",
			"quantity": 2,
			"unit_price": 50,
			"vat_rate": 15,
			"tax_amount": 15,
			"total": 100
		}
	]
}
```
**Response:**
ZATCA-compliant invoice XML.

---

#### 3. Sign Invoice

**POST** `/api/zatca/sign-invoice`

**Payload:**
```json
{
	"xml": "<Invoice>...</Invoice>"
}
```
**Response:**
Signed invoice XML (currently a stub; implement signing logic as needed).

---

#### 4. Report/Clear Invoice to ZATCA

**POST** `/api/zatca/report-invoice`

**Payload:**
```json
{
  "signed_xml": "<Invoice>...</Invoice>"
}
```
**Response:**
```json
{
  "uuid": "...",
  "clearanceStatus": "...",
  "reportingStatus": "...",
  // ...other ZATCA response fields
}
```

This endpoint sends your signed invoice XML to ZATCA's compliance API using your Device UUID and OAuth2 access token. The response contains the clearance/reporting status and other details from ZATCA.

---



