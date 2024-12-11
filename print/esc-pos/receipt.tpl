{strip}
{INIT}
{ESC hex="1B7400"}{* Select language page 0 *}

{LOGO}

{* Header Section *}
Oude Veerseweg 121{LF}
4332 SJ Middelburg{LF}
www.imkershop.nl{LF}{LF}

{* Title *}
Factuur{LF}
{DASHES}{LF}

{* Product Details *}
{foreach $entity->getProducts() as $product}
{$product.product_quantity} {$product.product_name} {DASHES} {displayPrice currency=$entity->id_currency price=$product.unit_price_tax_incl} {displayPrice currency=$entity->id_currency price=$product.unit_price_tax_excl}{LF}
{/foreach}

{* Totals *}
{DASHES}{LF}
Totaal {displayPrice currency=$entity->id_currency price=$entity->total_paid_tax_incl}{LF}
Btw: {$entity->tax_rate} {LF}

{* Payment Section *}
{$entity->payment}{LF}
{date('H:i d/m/Y')} Kassa 1 {LF}
{$entity->invoice_number}{LF} / {$entity->reference}{LF} {$entity->invoice_date}{LF}  
{DASHES}{LF}

{* Footer Section *}
Bedankt voor uw aankoop!{LF}
Niet blij met uw aankoop?{LF}
Retourneren binnen 60 dagen met bon.{LF}
{QR_CODE data="{$entity->reference}"}{LF}

{FULL_CUT}
{/strip}
