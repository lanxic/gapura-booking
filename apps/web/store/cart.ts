import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export type AddonItem = { addonId: string; qty: number; unitPrice: number }

export type TicketSelection = {
  variantId: string
  variantLabel: string
  qtyAdult: number
  qtyChild: number
  unitPriceAdult: number
  unitPriceChild: number
  addons: AddonItem[]
}

type PaymentOption =
  | { type: 'full' }
  | { type: 'down_payment'; percent: number }

type CartState = {
  productSlug: string | null
  selectedDate: string | null
  slotId: string | null
  tickets: TicketSelection[]
  voucher: { code: string; discount: number } | null
  paymentOption: PaymentOption

  setProduct: (slug: string) => void
  setDate: (date: string, slotId: string) => void
  setTicket: (ticket: TicketSelection) => void
  removeTicket: (variantId: string) => void
  setVoucher: (code: string, discount: number) => void
  clearVoucher: () => void
  setPaymentOption: (opt: PaymentOption) => void
  clear: () => void

  subtotal: () => number
  total: () => number
  dpAmount: () => number
  remainingAmount: () => number
}

export const useCartStore = create<CartState>()(
  persist(
    (set, get) => ({
      productSlug: null,
      selectedDate: null,
      slotId: null,
      tickets: [],
      voucher: null,
      paymentOption: { type: 'full' },

      setProduct: (slug) => set({ productSlug: slug }),
      setDate: (date, slotId) => set({ selectedDate: date, slotId }),
      setTicket: (ticket) =>
        set((s) => {
          const rest = s.tickets.filter((t) => t.variantId !== ticket.variantId)
          const updated =
            ticket.qtyAdult === 0 && ticket.qtyChild === 0
              ? rest
              : [...rest, ticket]
          return { tickets: updated }
        }),
      removeTicket: (variantId) =>
        set((s) => ({ tickets: s.tickets.filter((t) => t.variantId !== variantId) })),
      setVoucher: (code, discount) => set({ voucher: { code, discount } }),
      clearVoucher: () => set({ voucher: null }),
      setPaymentOption: (opt) => set({ paymentOption: opt }),
      clear: () =>
        set({
          productSlug: null,
          selectedDate: null,
          slotId: null,
          tickets: [],
          voucher: null,
          paymentOption: { type: 'full' },
        }),

      subtotal: () => {
        const { tickets } = get()
        return tickets.reduce((sum, t) => {
          const ticketTotal =
            t.qtyAdult * t.unitPriceAdult + t.qtyChild * t.unitPriceChild
          const addonTotal = t.addons.reduce((a, ad) => a + ad.qty * ad.unitPrice, 0)
          return sum + ticketTotal + addonTotal
        }, 0)
      },
      total: () => {
        const sub = get().subtotal()
        const disc = get().voucher?.discount ?? 0
        return Math.max(sub - disc, 0)
      },
      dpAmount: () => {
        const { paymentOption, total } = get()
        if (paymentOption.type === 'full') return total()
        return Math.ceil((total() * paymentOption.percent) / 100)
      },
      remainingAmount: () => {
        const { paymentOption, total, dpAmount } = get()
        if (paymentOption.type === 'full') return 0
        return total() - dpAmount()
      },
    }),
    { name: 'amartha-cart' }
  )
)
