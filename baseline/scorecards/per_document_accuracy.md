# Per-Document Accuracy Report

Field-level baseline results for each document. `code` shows the value the `SampleReceptionController` logic produced (mapped to the canonical field); `gold` is the ground truth. Result: ✅ exact · 🟡 partial · ❌ miss.

## Summary table

| Document | Type | Applicable | ✅ | 🟡 | ❌ | Strict | Weighted | Sample rows (code/gold) |
|---|---|--:|--:|--:|--:|--:|--:|:--:|
| 1911_GOLD-1 | actlabs_form | 14 | 6 | 6 | 2 | 42.9% | 64.3% | 234/1 |
| Alberta_Energy_Regulator-1 | actlabs_form | 12 | 5 | 5 | 2 | 41.7% | 62.5% | 19/1 |
| C3_METALS-1 | actlabs_form | 14 | 1 | 0 | 13 | 7.1% | 7.1% | 0/7 |
| CHEVRON-1 | client_form | 8 | 1 | 0 | 7 | 12.5% | 12.5% | 0/1 |
| Eion_Corp-1 | actlabs_form | 10 | 5 | 4 | 1 | 50.0% | 70.0% | 16/1 |
| Dahrouge-1 | actlabs_form | 10 | 4 | 3 | 3 | 40.0% | 55.0% | 16/1 |
| GEOPHYSX-1 | actlabs_form | 12 | 1 | 0 | 11 | 8.3% | 8.3% | 24/1 |
| LAURENTIA-1 | actlabs_form | 12 | 4 | 4 | 4 | 33.3% | 50.0% | 16/1 |
| LOMIKO-1 | actlabs_form | 12 | 5 | 3 | 4 | 41.7% | 54.2% | 5/2 |
| GGMC-1 | client_form | 6 | 1 | 0 | 5 | 16.7% | 16.7% | 142/3 |
| Goldcorp-1 | client_form | 8 | 1 | 0 | 7 | 12.5% | 12.5% | 0/1 |
| EMINCAR-1 | actlabs_form | 12 | 1 | 0 | 11 | 8.3% | 8.3% | 9/2 |
| RED_PINE-1 | actlabs_form | 13 | 1 | 0 | 12 | 7.7% | 7.7% | 200/1 |
| SAMA_NICKEL-1 | actlabs_form | 8 | 1 | 0 | 7 | 12.5% | 12.5% | 185/4 |
| PROBE-1 | client_form | 8 | 2 | 0 | 6 | 25.0% | 25.0% | 0/1 |
| Burkina_Inventory | decoy | — | — | — | — | — | — | 0/0 |
| Colombia_Inventory | decoy | — | — | — | — | — | — | 0/0 |
| Namibia_Inventory | decoy | — | — | — | — | — | — | 0/0 |
| MRNF-1 | client_form | 8 | 1 | 1 | 6 | 12.5% | 18.8% | 0/5 |

*Decoys have no applicable header fields (gold is all-null); the sample-row column shows spurious rows the code detected where gold expects 0.*

## Field-level detail

### 1911_GOLD-1  ·  `actlabs_form`

Applicable **14** · strict **42.9%** · weighted **64.3%**  (6✅ / 6🟡 / 2❌)

| Field | R | Code | Gold |
|---|:--:|---|---|
| company_name | 🟡 | 1911 Gold Corporation                                    … | 1911 Gold Corporation |
| contact_name | 🟡 | Devin Pickell                                            … | Devin Pickell |
| email | ✅ | dpickell@1911gold.com                                    … | dpickell@1911gold.com |
| phone | ✅ | 1-204-277-5411 x 256   Fax: 1-204-277-5552               … | 1-204-277-5411 x 256 |
| address | 🟡 | PO Box 1000 Bissett MB R0E 0J0                           … | PO Box 1000 Bissett MB R0E 0J0 |
| project | ✅ | 1911Gold | 1911Gold |
| quote_po_proforma | 🟡 | , PO #, Proforma #: 1911Gold-2020-01-R1 | 1911Gold-2020-01-R1; Client Batch CM22010 |
| carrier | 🟡 | Manitoulin               Waybill #: 24383319             … | Manitoulin |
| waybill | ✅ | 24383319 | 24383319 |
| num_packages | ✅ | 60 | 60 |
| num_samples | ❌ | 3 | 301 |
| priority | ✅ | Normal (may vary depending on package and time of year - … | Normal |
| payment_method | ❌ | For all clients, unless credit has been established, a su… | CreditEstablished |
| special_instructions | 🟡 | Return Pulps and Rejects every 90 days and send duplicate… | Return Pulps and Rejects every 90 days and send duplicate… |

**Sample table** — gold rows 1, code rows 234; gold total 301, code est 234; analysis-code overlap [] of gold ['1A2', '4LITHO', 'UT-1M']; prep-code overlap [] of gold ['RX1']

### Alberta_Energy_Regulator-1  ·  `actlabs_form`

Applicable **12** · strict **41.7%** · weighted **62.5%**  (5✅ / 5🟡 / 2❌)

| Field | R | Code | Gold |
|---|:--:|---|---|
| company_name | 🟡 | Alberta Energy Regulator                      METHOD OF P… | Alberta Energy Regulator |
| contact_name | ✅ | Dean Meek | Dean Meek |
| email | ✅ | dean.meek@aer.ca                                        C… | dean.meek@aer.ca |
| phone | ✅ | 780-642-9341                                            C… | 780-642-9341 |
| address | 🟡 | Suite 402, 4999 - 98 Avenue | Suite 402, 4999 - 98 Avenue, Edmonton, Alberta T6B 2X3 |
| project | 🟡 | 21RFP-SR008                                    MasterCard… | 21RFP-SR008 |
| quote_po_proforma | 🟡 | 4400002879                                             AM… | 4400002879 |
| num_packages | ✅ | 25 | 25 |
| num_samples | ✅ | 525 | 525 |
| priority | 🟡 | RUSH | Normal |
| payment_method | ❌ | Included | CreditEstablished |
| special_instructions | ❌ |  | Prep by RX15-CUT2 & RX-AGS (see instructions in Service A… |

**Sample table** — gold rows 1, code rows 19; gold total 525, code est 19; analysis-code overlap [] of gold ['4B1', '4LITHO-RESEARCH']; prep-code overlap [] of gold ['OTHER']

### C3_METALS-1  ·  `actlabs_form`

Applicable **14** · strict **7.1%** · weighted **7.1%**  (1✅ / 0🟡 / 13❌)

| Field | R | Code | Gold |
|---|:--:|---|---|
| company_name | ❌ |  | Carube Resources Jamaica Ltd. (C3 Metals Inc.) |
| contact_name | ❌ |  | Steve Hughes |
| email | ❌ |  | SHUGHES@C3METALS.COM |
| phone | ❌ |  | +1-647-517-4574 |
| address | ❌ |  | 161 Bay Street, 27th Floor, Toronto, ON, M5J 2S1 CANADA |
| project | ❌ |  | Bellas Gate |
| quote_po_proforma | ❌ |  | BG-23-003 |
| carrier | ❌ |  | DHL |
| waybill | ❌ |  | 60 0350 8604; Shipment 23-003 |
| num_packages | ❌ |  | 5 |
| num_samples | ❌ |  | 31 |
| priority | ✅ | Normal | Normal |
| payment_method | ❌ |  | Included |
| special_instructions | ❌ |  | run over range for Ag and Au automatically; store rejects… |

**Sample table** — gold rows 7, code rows 0; gold total 31, code est 0; analysis-code overlap [] of gold ['1A2B', '1A3', '1F-2', '8-4 ACID']; prep-code overlap [] of gold ['RX-1', 'RX10']

### CHEVRON-1  ·  `client_form`

Applicable **8** · strict **12.5%** · weighted **12.5%**  (1✅ / 0🟡 / 7❌)

| Field | R | Code | Gold |
|---|:--:|---|---|
| company_name | ❌ |  | Chevron |
| contact_name | ❌ |  | Michael Cheshire |
| email | ❌ |  | michael.cheshire@chevron.com |
| phone | ❌ |  | 713 954 6178 |
| address | ❌ |  | 3901 Briarpark Dr. BP532, Houston, TX 77042 |
| project | ❌ |  | HGTC23006 |
| num_samples | ❌ |  | 9 |
| priority | ✅ | Normal | Normal |

**Sample table** — gold rows 1, code rows 0; gold total 9, code est 0; analysis-code overlap [] of gold ['4 LITHORESEARCH', '4B1', '4F-CL', '4F-CO2', '4F-N', '4F-S', '5D-B', '5D-LI', 'GD']; prep-code overlap [] of gold ['RX6', 'RX7']

### Eion_Corp-1  ·  `actlabs_form`

Applicable **10** · strict **50.0%** · weighted **70.0%**  (5✅ / 4🟡 / 1❌)

| Field | R | Code | Gold |
|---|:--:|---|---|
| company_name | 🟡 | Eion                                                METHO… | Eion |
| contact_name | ✅ | Bill O'Connor | Bill O'Connor |
| email | ✅ | bill@eion.team                                           … | bill@eion.team |
| phone | ✅ | 541-905-4538                                             … | 541-905-4538 |
| address | 🟡 | 1546 Industrial Way SW                                   … | 1546 Industrial Way SW, Albany, OR 97322 |
| project | 🟡 | Rocks 2022                                               … | Rocks 2022 |
| num_packages | ✅ | 1 | 1 |
| num_samples | ✅ | 6 | 6 |
| priority | 🟡 | RUSH | Normal |
| payment_method | ❌ | Included | CreditCardOnFile |

**Sample table** — gold rows 1, code rows 16; gold total 6, code est 16; analysis-code overlap ['1G HG 5PPB', '4E-RES+ICPMS', '4F-B 2PPM'] of gold ['1G HG 5PPB', '4E-RES+ICPMS', '4F-B 2PPM']

### Dahrouge-1  ·  `actlabs_form`

Applicable **10** · strict **40.0%** · weighted **55.0%**  (4✅ / 3🟡 / 3❌)

| Field | R | Code | Gold |
|---|:--:|---|---|
| company_name | 🟡 | Dahrouge Geological Consulting                      METHO… | Dahrouge Geological Consulting |
| contact_name | ❌ | Failure to indicate | Neil McCallum |
| email | ✅ | neil@dahrouge.com                                        … | neil@dahrouge.com |
| phone | ✅ | 613-219-2975                                             … | 613-219-2975 |
| project | 🟡 | Dipole (Tripod)                                        Ma… | Dipole (Tripod) |
| quote_po_proforma | ❌ | AMEX                                                Expir… | 22708 |
| num_packages | ✅ | 1 | 1 |
| num_samples | ✅ | 11 | 11 |
| priority | 🟡 | RUSH | Normal |
| payment_method | ❌ | Included | CreditEstablished |

**Sample table** — gold rows 1, code rows 16; gold total 11, code est 16; analysis-code overlap ['UT7'] of gold ['UT7']

### GEOPHYSX-1  ·  `actlabs_form`

Applicable **12** · strict **8.3%** · weighted **8.3%**  (1✅ / 0🟡 / 11❌)

| Field | R | Code | Gold |
|---|:--:|---|---|
| company_name | ❌ |  | Geophysx Jamaica Ltd. |
| contact_name | ❌ |  | Robert Stewart |
| email | ❌ |  | robert.stewart@geophysxjamaica.com |
| phone | ❌ |  | 1-305-979-5326 |
| address | ❌ |  | 85 Hope Road, Kingston 6, Jamaica |
| project | ❌ |  | GPJ230711-R |
| carrier | ❌ |  | DHL |
| waybill | ❌ |  | 2009950810; Shipment GPJ230711-R |
| num_packages | ❌ |  | 3 |
| num_samples | ❌ |  | 30 |
| priority | ✅ | Normal | Normal |
| payment_method | ❌ |  | CreditCardOnFile |

**Sample table** — gold rows 1, code rows 24; gold total 30, code est 24; analysis-code overlap ['ULTRA TRACE 2'] of gold ['ULTRA TRACE 2']; prep-code overlap ['RX1-500 (74 MICRONS)'] of gold ['RX1-500 (74 MICRONS)']

### LAURENTIA-1  ·  `actlabs_form`

Applicable **12** · strict **33.3%** · weighted **50.0%**  (4✅ / 4🟡 / 4❌)

| Field | R | Code | Gold |
|---|:--:|---|---|
| company_name | 🟡 | Laurentia Exploration                                 MET… | Laurentia Exploration |
| contact_name | ❌ | Failure to indicate | Félix Gauthier Villeneuve |
| email | ✅ | felix.gv@laurentiaexploration.com                        … | felix.gv@laurentiaexploration.com |
| phone | ✅ | 5812341632                                               … | 5812341632 |
| address | 🟡 | 3415 rue de l'�nergie, Jonqui�re, Qc, G7X 0J6            … | 3415 rue de l'Énergie, Jonquière, Qc, G7X 0J6 |
| project | 🟡 | B�gin-Lamarche                                           … | Bégin-Lamarche |
| quote_po_proforma | ❌ | AMEX                                              Expiry … | 22-015 |
| num_packages | ✅ | 19 | 19 |
| num_samples | ✅ | 82 | 82 |
| priority | 🟡 | RUSH | Normal |
| payment_method | ❌ | Included | Unknown |
| special_instructions | ❌ |  | Please also send the results to John Passalacqua (johnpas… |

**Sample table** — gold rows 1, code rows 16; gold total 82, code est 16; analysis-code overlap ['4B1'] of gold ['4B1']

### LOMIKO-1  ·  `actlabs_form`

Applicable **12** · strict **41.7%** · weighted **54.2%**  (5✅ / 3🟡 / 4❌)

| Field | R | Code | Gold |
|---|:--:|---|---|
| company_name | 🟡 | Lomiko Metals                                            … | Lomiko Metals |
| contact_name | 🟡 | Mark Fekete                                              … | Mark Fekete |
| email | ✅ | mark@breakawayx.com                                      … | mark@breakawayx.com |
| phone | ✅ | 819-354-5244            Fax:                             … | 819-354-5244 |
| address | ❌ | 439 7184 120th Street                                    … | 439 – 7184 120th Street, Surrey, British Columbia V3W 0M6 |
| project | ❌ |  | GC Regional |
| quote_po_proforma | ❌ | , PO #, Proforma #: Lomiko | Lomiko; Client Batch Lomiko Metals |
| carrier | 🟡 | Breakaway                      Waybill #:                … | Breakaway |
| num_packages | ✅ | 3 | 3 |
| num_samples | ✅ | 63 | 63 |
| priority | ✅ | Normal (may vary depending on package and time of year - … | Normal |
| payment_method | ❌ | For all clients, unless credit has been established, a su… | CreditEstablished |

**Sample table** — gold rows 2, code rows 5; gold total 63, code est 5; analysis-code overlap [] of gold ['CODE 8 C - GRAPHITIC (INFRARED)']; prep-code overlap [] of gold ['RX!', 'RX1']

### GGMC-1  ·  `client_form`

Applicable **6** · strict **16.7%** · weighted **16.7%**  (1✅ / 0🟡 / 5❌)

| Field | R | Code | Gold |
|---|:--:|---|---|
| company_name | ❌ |  | Guyana Geology and Mines Commission |
| contact_name | ❌ |  | Newell Dennison |
| email | ❌ |  | commissioner@ggmc.gov.gy |
| phone | ✅ | (592) 225-2862 Ext: 245 | (592) 225-2862 Ext: 245 |
| address | ❌ |  | 68 Upper Brickdam, Georgetown, Guyana, South America |
| special_instructions | ❌ |  | Rock: Prep RX2, Analysis 1H (Au+48). Stream sediments: as… |

**Sample table** — gold rows 3, code rows 142; gold total 2198, code est 142; analysis-code overlap [] of gold ['1A6', '1H', '4B', '7-MIG', 'WRA-ICP']; prep-code overlap [] of gold ['1S', 'RX2', 'S1']

### Goldcorp-1  ·  `client_form`

Applicable **8** · strict **12.5%** · weighted **12.5%**  (1✅ / 0🟡 / 7❌)

| Field | R | Code | Gold |
|---|:--:|---|---|
| company_name | ❌ |  | Goldcorp Canada (Newmont Éléonore) |
| contact_name | ❌ |  | Daniel Daoust |
| email | ❌ |  | Jean-Francois.Croteau@newmont.com |
| phone | ❌ |  | 819-865-4080 |
| address | ❌ |  | 1751, rue Davy, Rouyn-Noranda, Québec, J9Y 0A8 |
| waybill | ❌ |  | ENVP5749 |
| num_samples | ❌ |  | 300 |
| priority | ✅ | Normal | Normal |

**Sample table** — gold rows 1, code rows 0; gold total 300, code est 0; analysis-code overlap [] of gold ['1A2 + 1A3>3G/T']

### EMINCAR-1  ·  `actlabs_form`

Applicable **12** · strict **8.3%** · weighted **8.3%**  (1✅ / 0🟡 / 11❌)

| Field | R | Code | Gold |
|---|:--:|---|---|
| company_name | ❌ |  | EMINCAR S.A |
| contact_name | ❌ |  | Santiago Castro |
| email | ❌ |  | santiago.castro@emincar.com |
| phone | ❌ |  | +53 52796272 |
| address | ❌ |  | Ave. 3ra entre 76 y 78 Edificio Santa Clara, Oficina No. … |
| quote_po_proforma | ❌ |  | 21040 S 0000 |
| carrier | ❌ |  | UPS |
| waybill | ❌ |  | 1Z30638A0406599798 |
| num_packages | ❌ |  | 1 |
| num_samples | ❌ |  | 6 |
| priority | ✅ | Normal | Normal |
| payment_method | ❌ |  | Unknown |

**Sample table** — gold rows 2, code rows 9; gold total 6, code est 6; analysis-code overlap ['AG', 'S', 'ZN'] of gold ['AG', 'S', 'ZN']

### RED_PINE-1  ·  `actlabs_form`

Applicable **13** · strict **7.7%** · weighted **7.7%**  (1✅ / 0🟡 / 12❌)

| Field | R | Code | Gold |
|---|:--:|---|---|
| company_name | ❌ |  | Red Pine Exploration |
| contact_name | ❌ |  | Quentin Yarie |
| email | ❌ |  | qyarie@redpineexp.com |
| phone | ❌ |  | (416) 364-7024 |
| address | ❌ |  | 145 Wellington St W, Suite 1001, Toronto, ON, M5J 1H8 |
| project | ❌ |  | Wawa Gold Project |
| quote_po_proforma | ❌ |  | Wawa Gold Project |
| carrier | ❌ |  | Manitoulin Transport |
| num_packages | ❌ |  | 101 |
| num_samples | ❌ |  | 405 |
| priority | ✅ | Normal | Normal |
| payment_method | ❌ |  | CreditEstablished |
| special_instructions | ❌ |  | Please refer to attached spreadsheet. |

**Sample table** — gold rows 1, code rows 200; gold total 405, code est 200; analysis-code overlap [] of gold ['1A2-50', 'UT-6M-REDPINE']; prep-code overlap ['RX-1'] of gold ['RX-1']

### SAMA_NICKEL-1  ·  `actlabs_form`

Applicable **8** · strict **12.5%** · weighted **12.5%**  (1✅ / 0🟡 / 7❌)

| Field | R | Code | Gold |
|---|:--:|---|---|
| company_name | ❌ |  | SAMA NICKEL CI SARL |
| email | ❌ |  | bakayokobouake@hotmail.com |
| phone | ❌ |  | (225) 05 44 32 71 91 |
| address | ❌ |  | 28 BP 1467 ABIDJAN 28 |
| project | ❌ |  | PERMIT NUMBER 837, 839 & 604 |
| num_samples | ❌ |  | 179 |
| priority | ✅ | Normal | Normal |
| payment_method | ❌ |  | Unknown |

**Sample table** — gold rows 4, code rows 185; gold total 179, code est 185; analysis-code overlap [] of gold ['1C-OES AU, PT, PD', '8-NA PEROXIDE FUSION ICP FULL REPORT']; prep-code overlap ['NONE'] of gold ['NONE']

### PROBE-1  ·  `client_form`

Applicable **8** · strict **25.0%** · weighted **25.0%**  (2✅ / 0🟡 / 6❌)

| Field | R | Code | Gold |
|---|:--:|---|---|
| company_name | ❌ |  | Probe Gold |
| contact_name | ❌ |  | Matthieu Dessureault |
| email | ❌ |  | mdessureault@probegold.com |
| phone | ❌ |  | 819-860-2769 |
| address | ❌ |  | 1338 Rue Turcotte |
| project | ✅ | SENORE | SENORE |
| num_samples | ❌ |  | 98 |
| priority | ✅ | Normal | Normal |

**Sample table** — gold rows 1, code rows 0; gold total 98, code est 0

### Burkina_Inventory  ·  `decoy`

Decoy (inventory). Gold expects all fields null and `total_samples = 0`.

- False-positive *canonical* header fields: **1** — `quote_po_proforma`
- Raw junk fields emitted by the key/value sweep (inventory columns read as labels): **82**
- Spurious sample rows detected: **0** (gold = 0)

### Colombia_Inventory  ·  `decoy`

Decoy (inventory). Gold expects all fields null and `total_samples = 0`.

- False-positive *canonical* header fields: **1** — `quote_po_proforma`
- Raw junk fields emitted by the key/value sweep (inventory columns read as labels): **114**
- Spurious sample rows detected: **0** (gold = 0)

### Namibia_Inventory  ·  `decoy`

Decoy (inventory). Gold expects all fields null and `total_samples = 0`.

- False-positive *canonical* header fields: **1** — `quote_po_proforma`
- Raw junk fields emitted by the key/value sweep (inventory columns read as labels): **51**
- Spurious sample rows detected: **0** (gold = 0)

### MRNF-1  ·  `client_form`

Applicable **8** · strict **12.5%** · weighted **18.8%**  (1✅ / 1🟡 / 6❌)

| Field | R | Code | Gold |
|---|:--:|---|---|
| company_name | ❌ |  | Ministère des Ressources naturelles et des Forêts (MRNF /… |
| contact_name | ❌ |  | Théo Hassen Ali |
| email | ❌ |  | Olivier.Lamarche@mern.gouv.qc.ca |
| phone | ❌ |  | 438-508-1894 |
| project | ❌ |  | HASSEN |
| quote_po_proforma | 🟡 | nsable à la DACG: Olivier.Lamarche@mern.gouv.qc.ca \| Dem… | Demande No : 1 |
| num_samples | ❌ |  | 50 |
| priority | ✅ | Normal | Normal |

**Sample table** — gold rows 5, code rows 0; gold total 50, code est 0; analysis-code overlap [] of gold ['1C-EXP2', '8', 'QC-TOT', 'S-TOT']; prep-code overlap [] of gold ['RX1']
