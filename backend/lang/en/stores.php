<?php
// Store info / receipt settings strings (English)
return [
    'stores.page_title'          => 'Store Information',
    'stores.section_edit_info'   => 'Edit Store / Company Information',

    // Logo block
    'stores.logo_change_title'   => 'Change logo image',
    'stores.logo_heading'        => 'Store Logo',
    'stores.logo_help'           => 'Supports JPG, PNG, WEBP (500x500px)',
    'stores.logo_choose_btn'     => 'Choose logo image',
    'stores.shared_info_note'    => 'Store name, address, phone and tax information are shared from the main branch and can only be edited there — only the "Logo" above can be changed per branch.',

    // Form labels
    'stores.field_name'          => 'Store / Company name:',
    'stores.field_name_ph'       => 'Enter store name...',
    'stores.field_tel'           => 'Contact phone number:',
    'stores.field_address'       => 'Store address (shown on receipt):',
    'stores.field_address_ph'    => 'Village, district, province...',
    'stores.field_tax_id'        => 'Taxpayer ID:',
    'stores.field_tax_id_ph'     => 'Enter taxpayer ID (if left blank, it will not be shown on the receipt)...',
    'stores.tax_id_note'         => 'Note: if a taxpayer ID is entered, it is shown on the receipt automatically; if left blank, it is not shown.',

    // VAT config
    'stores.vat_section_label'   => 'Value Added Tax (VAT) configuration:',
    'stores.tax_inclusive'       => 'Tax inclusive',
    'stores.tax_inclusive_hint'  => '(Inclusive - price already includes VAT)',
    'stores.tax_exclusive'       => 'Tax exclusive',
    'stores.tax_exclusive_hint'  => '(Exclusive - add VAT % on top)',
    'stores.tax_none'            => 'No value added tax (0%)',
    'stores.vat_rate'            => 'VAT rate (%):',
    'stores.vat_rate_ph'         => 'Please enter the VAT rate %',

    // Receipt footer
    'stores.field_footer'        => 'Receipt footer message:',
    'stores.field_footer_ph'     => 'e.g. Thank you for your purchase, goods sold are non-refundable...',

    // Queue number on receipt
    'stores.field_queue_enabled' => 'Show queue number on receipt (cafés / shops with a queue)',
    'stores.field_queue_hint'    => 'Queue numbers restart at 1 every day and print large above the logo. Leave this off for regular shops.',

    // Buttons
    'stores.btn_save_info'       => 'Save store information',
    'stores.btn_save_logo'       => 'Save logo',
];
