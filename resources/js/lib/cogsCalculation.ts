// Types
export type PriceMode = 'final' | 'before_discount'
export type DiscountType = 'percent' | 'nominal' | null

export interface InvoiceItemInput {
    product_id: string
    qty: number
    price_input: number
    price_mode: PriceMode
    discount_item_type?: DiscountType
    discount_item_value?: number
}

export interface InvoiceItemWithCogs extends InvoiceItemInput {
    price_per_unit_final: number
    global_discount_portion: number
    cogs_per_unit: number
    subtotal_final: number
}

export interface InvoiceSummary {
    total_before_discount: number
    total_discount: number
    total_final: number
    items_with_cogs: InvoiceItemWithCogs[]
}

/**
 * Calculate the final price per unit after applying per-item discount.
 *
 * - If priceMode === 'final': return priceInput (discount is ignored)
 * - If priceMode === 'before_discount' and discountType === 'percent':
 *     return priceInput * (1 - discountValue / 100)
 * - If priceMode === 'before_discount' and discountType === 'nominal':
 *     return priceInput - discountValue
 * - If priceMode === 'before_discount' and no discount: return priceInput
 * - Always returns max(0, result)
 */
export function calculatePricePerUnitFinal(
    priceInput: number,
    priceMode: PriceMode,
    discountType: DiscountType,
    discountValue: number,
): number {
    if (priceMode === 'final') {
        return Math.max(0, priceInput)
    }

    // priceMode === 'before_discount'
    if (discountType === 'percent') {
        return Math.max(0, priceInput * (1 - discountValue / 100))
    }

    if (discountType === 'nominal') {
        return Math.max(0, priceInput - discountValue)
    }

    // No discount
    return Math.max(0, priceInput)
}

/**
 * Distribute a global discount proportionally across items based on each item's subtotal.
 *
 * - If total subtotal is 0, returns an array of zeros.
 * - Returns an array of global_discount_portion per item.
 */
export function distributeGlobalDiscount(
    items: Array<{ subtotal: number }>,
    globalDiscount: number,
): number[] {
    const totalSubtotal = items.reduce((sum, item) => sum + item.subtotal, 0)

    if (totalSubtotal === 0) {
        return items.map(() => 0)
    }

    return items.map((item) => (item.subtotal / totalSubtotal) * globalDiscount)
}

/**
 * Calculate COGS per unit for an invoice item.
 *
 * Formula: max(0, (pricePerUnitFinal * qty - globalDiscountPortion) / qty)
 * If qty is 0, returns 0.
 */
export function calculateCogsPerUnit(
    pricePerUnitFinal: number,
    qty: number,
    globalDiscountPortion: number,
): number {
    if (qty === 0) {
        return 0
    }

    return Math.max(0, (pricePerUnitFinal * qty - globalDiscountPortion) / qty)
}

/**
 * Calculate the complete invoice summary including COGS per unit for each item.
 *
 * Steps:
 * 1. Calculate price_per_unit_final for each item
 * 2. Calculate total_before_discount = SUM(price_per_unit_final * qty)
 * 3. Calculate total_discount from globalDiscountType and globalDiscountValue
 * 4. Distribute global discount proportionally
 * 5. Calculate cogs_per_unit for each item
 * 6. Return complete summary
 */
export function calculateInvoiceSummary(
    items: InvoiceItemInput[],
    globalDiscountType: DiscountType,
    globalDiscountValue: number,
): InvoiceSummary {
    // Step 1: Calculate price_per_unit_final for each item
    const itemsWithFinalPrice = items.map((item) => {
        const price_per_unit_final = calculatePricePerUnitFinal(
            item.price_input,
            item.price_mode,
            item.discount_item_type ?? null,
            item.discount_item_value ?? 0,
        )
        const subtotal_before_global = price_per_unit_final * item.qty
        return { ...item, price_per_unit_final, subtotal_before_global }
    })

    // Step 2: Calculate total_before_discount
    const total_before_discount = itemsWithFinalPrice.reduce(
        (sum, item) => sum + item.subtotal_before_global,
        0,
    )

    // Step 3: Calculate total_discount from global discount
    let total_discount = 0
    if (globalDiscountType === 'percent') {
        total_discount = total_before_discount * (globalDiscountValue / 100)
    } else if (globalDiscountType === 'nominal') {
        total_discount = globalDiscountValue
    }

    // Ensure total_discount does not exceed total_before_discount
    total_discount = Math.min(total_discount, total_before_discount)
    total_discount = Math.max(0, total_discount)

    // Step 4: Distribute global discount proportionally
    const subtotals = itemsWithFinalPrice.map((item) => ({ subtotal: item.subtotal_before_global }))
    const globalDiscountPortions = distributeGlobalDiscount(subtotals, total_discount)

    // Step 5: Calculate cogs_per_unit and build final items
    const items_with_cogs: InvoiceItemWithCogs[] = itemsWithFinalPrice.map((item, index) => {
        const global_discount_portion = globalDiscountPortions[index]
        const cogs_per_unit = calculateCogsPerUnit(
            item.price_per_unit_final,
            item.qty,
            global_discount_portion,
        )
        const subtotal_final = item.subtotal_before_global - global_discount_portion

        return {
            product_id: item.product_id,
            qty: item.qty,
            price_input: item.price_input,
            price_mode: item.price_mode,
            discount_item_type: item.discount_item_type,
            discount_item_value: item.discount_item_value,
            price_per_unit_final: item.price_per_unit_final,
            global_discount_portion,
            cogs_per_unit,
            subtotal_final,
        }
    })

    const total_final = total_before_discount - total_discount

    return {
        total_before_discount,
        total_discount,
        total_final,
        items_with_cogs,
    }
}
