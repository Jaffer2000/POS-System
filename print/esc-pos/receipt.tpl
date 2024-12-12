{strip}
{INIT}
{ESC hex="1B7404"}{* Select code page 4 (CP858 with Euro) *}

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
{$product.product_quantity} {$product.product_name} {displayPrice currency=$entity->id_currency price=$product.unit_price_tax_excl} {displayPrice currency=$entity->id_currency price=$product.unit_price_tax_incl}{LF}
{/foreach}

{* Totals *}
{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{DASHES}{LF}{LF}
Totaal {displayPrice currency=$entity->id_currency price=$entity->total_paid_tax_incl}{LF}{LF}
{foreach $taxBreakdown as $rate => $breakdown}
Btw {round($rate, 2)}%: {displayPrice currency=$entity->id_currency price=$breakdown.total_amount}{LF}{LF}
{/foreach}

{* Payment Section *}
{$paymentMethod.name}{LF}{LF}
{date('H:i d/m/Y')} {$workstation.name}{LF}
{$entity->invoice_number} / {$entity->reference} {dateFormat date=$entity->date_add|trim}{LF}
{LF}
{LF}

{* Footer Section *}
{ESC hex="1B6101"}{* Center alignment *}
Bedankt voor uw aankoop!{LF}
{LF}
Niet blij met uw aankoop?{LF}
Retourneren binnen 60 dagen met bon.{LF}

{ESC hex="1B6100"}{* Reset to left alignment *}

{LF}
{LF}
{LF}
{LF}
{LF}
{LF}

{FULL_CUT}
{/strip}
