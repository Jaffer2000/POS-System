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
{displayPrice currency=$entity->id_currency price=$product.unit_price_tax_excl|string_format:"%10s"} 
{displayPrice currency=$entity->id_currency price=$product.unit_price_tax_incl|string_format:"%10s"}{LF}
{/foreach}

{* Totals *}
{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{LF}{LF}
{"Totaal"|string_format:"%-36s"} {displayPrice currency=$entity->id_currency price=$entity->total_paid_tax_incl|string_format:"%10s"}{LF}{LF}
{foreach $taxBreakdown as $rate => $breakdown}
{"Btw %s%%"|sprintf:round($rate, 2)|string_format:"%-37s"}{displayPrice currency=$entity->id_currency price=$breakdown.total_amount|string_format:"%10.2f"}{LF}
{/foreach}

{LF}

{* Payment Section *}
{$paymentMethod.name}{LF}{LF}
{date('H:i d/m/Y')} {$workstation.name}{LF}
{$entity->invoice_number} / {$entity->reference} {dateFormat date=$entity->date_add|trim}{LF}
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
