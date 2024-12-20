{strip}
{INIT}
{ESC hex="1B7403"} {* Select Windows-1252 (Latin-1) code page *}

{* Header Section *}
{ESC hex="1B6101"}{* Center alignment *}
Oude Veerseweg 121{LF}
4332 SJ Middelburg{LF}
www.imkershop.nl{LF}{LF}
{ESC hex="1B6100"}{* Reset to left alignment *}

{* Title *}
Factuur{LF}
{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{LF}

{* Product Details *}
{foreach $entity->getProducts() as $product}
{$product.product_quantity|string_format:"%-3s"} 
{$product.product_name|truncate:25:""|string_format:"%-25s"} 
{round($product.unit_price_tax_excl, 2)|string_format:"%10.2f"} 
{round($product.unit_price_tax_incl, 2)|string_format:"%10.2f"}{LF}
{/foreach}

{* Totals *}
{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{LF}{LF}
{"Totaal"|string_format:"%-37s"} {round($entity->total_paid_tax_incl, 2)|string_format:"%10.2f"}{LF}{LF}
{foreach $taxBreakdown as $rate => $breakdown}
{"Btw %s%%"|sprintf:round($rate, 2)|string_format:"%-37s"} {round($breakdown.total_amount, 2)|string_format:"%10.2f"}{LF}
{/foreach}

{LF}

{* Payment Section *}
{$paymentMethod.name}{LF}{LF}
{date('H:i d/m/Y')} {$workstation.name}{LF}
{$entity->invoice_number} / {$entity->reference} {date('d/m/Y', strtotime($entity->date_add))}{LF}
{LF}
{LF}

{* Footer Section *}
{ESC hex="1B6101"}{* Center alignment *}
Bedankt voor uw aankoop!{LF}

{ESC hex="1B6100"}{* Reset to left alignment *}

{LF}
{LF}
{LF}
{LF}
{LF}
{LF}

{FULL_CUT}
{/strip}
