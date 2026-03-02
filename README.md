# LibData-BI-Integration: Library Data Warehouse & Analytics
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Power Bi](https://img.shields.io/badge/power_bi-F2C811?style=for-the-badge&logo=powerbi&logoColor=black)
![PMB](https://img.shields.io/badge/PMB-Sigb-orange?style=for-the-badge)
>**This project demonstrates a complete Business Intelligence (BI) workflow for modernizing library management. It focuses on the ETL (Extract, Transform, Load) process of migrating metadata from disparate sources into a standardized Integrated Library System (ILS) and providing actionable insights through data visualization.**

## Overview
The project addresses the challenge of handling heterogeneous data sources (`buf.csv` and `bua.xls`) containing bibliographic metadata. By applying rigorous cleaning and transformation scripts, the data is unified and injected into **PMB (PhpMyBibli)**, followed by advanced analysis using **Power BI**.

### Key Features
* **Data Cleaning:** Removal of duplicates, handling missing values ("N/A"), and fixing encoding issues.
* **ETL Pipeline:** Custom PHP scripts for automated data transformation and MySQL integration.
* **SIGB Integration:** Full cataloging into PMB, including authors, publishers, and Dewey-style indexing.
* **BI Dashboards:** Interactive Power BI reports analyzing loan trends, user behavior, and collection status.

## Project Structure

* `bua_cleaner.php`: Cleans and normalizes the Excel-based source (`bua.xls`), handling Arabic character sets and inventory numbering.
* `buf_cleaner.php`: Processes the CSV source (`buf.csv`), performing encoding conversion (ISO-8859-1 to UTF-8) and special character stripping.
* `import_to_pmb.php`: The main migration script that maps cleaned data to the PMB MySQL schema (notices, exemplaires, authors, etc.).
* `RapportBI.pdf`: Comprehensive project report detailing the methodology and results.

## Technical Stack

* **Environment:** XAMPP (Apache, MySQL, PHP 8.x).
* **Library System:** PMB (PhpMyBibli) Open Source SIGB.
* **BI Tool:** Microsoft Power BI & Power Query.
* **PHP Libraries:** PhpSpreadsheet for Excel processing.

## Getting Started

### Prerequisites
1.  Install **XAMPP**.
2.  Deploy **PMB** in your `htdocs` folder and complete the web installation.
3.  Create a database named `pmb` in phpMyAdmin.

### Installation & Usage
1.  **Clean Data:** Run the cleaning scripts to generate sanitized CSV files.
    ```bash
    php bua_cleaner.php
    php buf_cleaner.php
    ```
2.  **Import to PMB:** Execute the import script to populate the library database.
    ```bash
    php import_to_pmb.php
    ```
3.  **Reindex:** Access the PMB administration panel and run the "Database cleanup" tool to update search indexes.
4.  **Visualize:** Connect Power BI to the MySQL database to view the analytics dashboards.

## Results
The pipeline successfully processed and integrated thousands of records:
* **Records Processed:** ~22,540 
* **Authors Indexed:** ~21,373 
* **Publishers Indexed:** ~22,423

## Contributors
* Wijdan ELBAKHOUCHI 
* Maryam CHOUKI 
* Boutayna DAKKI 
* **Supervisor:** Pr. Mohamed AMMARI 

---
*Academic Project - Université Mohammed V de Rabat (2024-2025)*
