{strip}
{INIT}
{ESC hex="1B7400"}{* select language page 0 *}
Order #{$entity->reference}{LF}
{foreach $entity->getProducts() as $product}
{$product.product_name} - {$product.product_quantity}{LF}
{/foreach}
{FULL_CUT}
{/strip}