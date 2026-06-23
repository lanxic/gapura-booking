'use client'

import { useState, useRef, useEffect } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { formatRupiah } from 'ui'
import { useCartStore } from '@/store/cart'
import type { Product, ProductVariant, ApiResponse } from '@/types'
import {
  Calendar, Users, ChevronDown, ChevronLeft, ChevronRight,
  Zap, ShoppingCart, Check, Tag, X, MapPin,
} from 'lucide-react'

type HeroSettings = {
  title: string
  subtitle: string
  image_url: string
  cta_label: string
  cta_url: string
}

function getTodayISO() {
  return new Date().toISOString().split('T')[0]
}

function fmtDate(iso: string) {
  return new Date(iso + 'T00:00:00').toLocaleDateString('en-GB', {
    weekday: 'short', day: '2-digit', month: 'long', year: 'numeric',
  })
}

// ─── Hero ─────────────────────────────────────────────────────────────────────
function HeroSection({ hero, thumb }: { hero?: HeroSettings | null; thumb?: string | null }) {
  const image = hero?.image_url ?? thumb ?? null

  return (
    <section className="relative overflow-hidden bg-gray-900 min-h-[340px] flex items-center">
      {image ? (
        <img src={image} alt="" className="absolute inset-0 h-full w-full object-cover opacity-40" />
      ) : (
        <div className="absolute inset-0 bg-gradient-to-br from-emerald-900 via-teal-800 to-slate-900" />
      )}
      <div className="relative mx-auto max-w-5xl w-full px-4 py-20 text-center text-white">
        <h1 className="text-3xl font-bold tracking-tight sm:text-5xl leading-tight">
          {hero?.title ?? 'Find the Best Tour Experience'}
        </h1>
        <p className="mt-4 text-base text-white/70 sm:text-lg max-w-2xl mx-auto">
          {hero?.subtitle ?? 'Book your tour tickets online, easily and securely.'}
        </p>
      </div>
    </section>
  )
}

// ─── Guest Picker ─────────────────────────────────────────────────────────────
function GuestPicker({
  adults,
  childCount,
  onAdults,
  onChildren,
}: {
  adults: number
  childCount: number
  onAdults: (n: number) => void
  onChildren: (n: number) => void
}) {
  const [open, setOpen] = useState(false)
  const ref = useRef<HTMLDivElement>(null)

  useEffect(() => {
    function outside(e: MouseEvent) {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false)
    }
    document.addEventListener('mousedown', outside)
    return () => document.removeEventListener('mousedown', outside)
  }, [])

  const label = `${adults} Adult${adults !== 1 ? 's' : ''}${childCount > 0 ? `, ${childCount} Child${childCount !== 1 ? 'ren' : ''}` : ''}`

  return (
    <div className="relative z-20" ref={ref}>
      <button
        onClick={() => setOpen(o => !o)}
        className="flex items-center gap-1.5 text-sm font-semibold text-gray-800 hover:text-gray-900 transition-colors whitespace-nowrap"
      >
        {label}
        <ChevronDown className="w-3.5 h-3.5 text-gray-400" />
      </button>

      {open && (
        <div className="absolute left-0 top-full z-50 mt-2 w-72 rounded-2xl border border-gray-200 bg-white p-6 space-y-5 shadow-2xl">
          {[
            { label: 'Adult', sub: 'Age 12+', value: adults, min: 1, onChange: onAdults },
            { label: 'Children', sub: 'Age 1–11', value: childCount, min: 0, onChange: onChildren },
          ].map(({ label, sub, value, min, onChange }) => (
            <div key={label} className="flex items-center justify-between">
              <div>
                <p className="text-sm font-bold text-gray-900">{label}</p>
                <p className="text-xs text-gray-400">{sub}</p>
              </div>
              <div className="flex items-center gap-3">
                <button
                  onClick={() => onChange(Math.max(min, value - 1))}
                  className="w-8 h-8 flex items-center justify-center rounded-full border-2 border-gray-200 text-gray-600 hover:border-gray-900 hover:text-gray-900 transition-colors"
                >−</button>
                <span className="w-5 text-center text-sm font-bold text-gray-900">{value}</span>
                <button
                  onClick={() => onChange(value + 1)}
                  className="w-8 h-8 flex items-center justify-center rounded-full border-2 border-gray-200 text-gray-600 hover:border-gray-900 hover:text-gray-900 transition-colors"
                >+</button>
              </div>
            </div>
          ))}
          <button
            onClick={() => setOpen(false)}
            className="w-full py-2.5 bg-gray-900 hover:bg-gray-700 text-white text-sm font-bold rounded-xl transition-colors"
          >
            Done
          </button>
        </div>
      )}
    </div>
  )
}

// ─── Search Bar ───────────────────────────────────────────────────────────────
function SearchBar({
  date, onDateChange, adults, childCount, onAdults, onChildren,
}: {
  date: string
  onDateChange: (d: string) => void
  adults: number
  childCount: number
  onAdults: (n: number) => void
  onChildren: (n: number) => void
}) {
  const dateRef = useRef<HTMLInputElement>(null)

  function openDatePicker() {
    try { dateRef.current?.showPicker() } catch { dateRef.current?.click() }
  }

  return (
    <div className="mx-auto max-w-5xl px-4 -mt-6 relative z-10">
      <div className="rounded-2xl bg-white shadow-xl border border-gray-100 flex flex-col sm:flex-row overflow-visible">
        {/* Date */}
        <button
          onClick={openDatePicker}
          className="flex items-center gap-3 px-5 py-4 flex-1 border-b sm:border-b-0 sm:border-r border-gray-100 hover:bg-gray-50 transition-colors text-left rounded-t-2xl sm:rounded-l-2xl sm:rounded-tr-none"
        >
          <Calendar className="h-5 w-5 flex-shrink-0 text-emerald-600" />
          <div>
            <p className="text-[10px] font-bold uppercase tracking-widest text-gray-400">Visit Date</p>
            <p className="mt-0.5 text-sm font-semibold text-gray-800 select-none">{fmtDate(date)}</p>
          </div>
          <input
            ref={dateRef}
            type="date"
            value={date}
            min={getTodayISO()}
            onChange={(e) => onDateChange(e.target.value)}
            className="sr-only"
          />
        </button>

        {/* Guest */}
        <div className="flex items-center gap-3 px-5 py-4 flex-1 border-b sm:border-b-0 sm:border-r border-gray-100 overflow-visible">
          <Users className="h-5 w-5 flex-shrink-0 text-emerald-600" />
          <div className="flex-1 min-w-0">
            <p className="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-0.5">Guests</p>
            <GuestPicker adults={adults} childCount={childCount} onAdults={onAdults} onChildren={onChildren} />
          </div>
        </div>

        {/* CTA */}
        <div className="flex items-center px-4 py-3 sm:py-0 rounded-b-2xl sm:rounded-r-2xl sm:rounded-bl-none">
          <a
            href="#tickets"
            className="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-xl text-sm font-semibold transition-colors text-center whitespace-nowrap"
          >
            Search Tickets
          </a>
        </div>
      </div>
    </div>
  )
}

// ─── Product Card ─────────────────────────────────────────────────────────────
function ProductCard({
  product, adults, childCount, visitDate,
}: {
  product: Product
  adults: number
  childCount: number
  visitDate: string
}) {
  const [currentImg, setCurrentImg] = useState(0)
  const [selectedVariantId, setSelectedVariantId] = useState<string | null>(null)
  const cart = useCartStore()

  function handleSelect(variant: ProductVariant) {
    cart.setProduct(product.slug, product.name)
    cart.setDate(visitDate, 'all-day')
    cart.setTicket({
      productId: variant.productId,
      variantId: variant.id,
      variantLabel: variant.label,
      qtyAdult: adults,
      qtyChild: childCount,
      unitPriceAdult: variant.priceAdult,
      unitPriceChild: variant.priceChild,
      addons: [],
    })
    setSelectedVariantId(variant.id)
  }

  const images = [product.cloudinaryImageUrl, ...product.cloudinaryGalleryUrls].filter(Boolean) as string[]
  const activeVariants = product.variants.filter(v => v.isActive)
  const primaryVariant = activeVariants[0] ?? null
  const grandTotal = primaryVariant
    ? primaryVariant.priceAdult * adults + (primaryVariant.priceChild > 0 ? primaryVariant.priceChild * childCount : 0)
    : 0

  return (
    <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-shadow">
      <div className="flex flex-col sm:flex-row">
        {/* Image */}
        <div className="relative sm:w-56 flex-shrink-0 overflow-hidden bg-gray-100" style={{ minHeight: 200 }}>
          {images.length > 0 ? (
            <>
              <div
                className="flex h-full transition-transform duration-300 ease-out"
                style={{ width: `${images.length * 100}%`, transform: `translateX(-${(currentImg / images.length) * 100}%)` }}
              >
                {images.map((url, i) => (
                  <div key={i} className="h-full flex-shrink-0" style={{ width: `${100 / images.length}%` }}>
                    {/* eslint-disable-next-line @next/next/no-img-element */}
                    <img src={url} alt={product.name} className="h-full w-full object-cover" style={{ minHeight: 200 }} />
                  </div>
                ))}
              </div>
              {images.length > 1 && (
                <>
                  <button onClick={() => setCurrentImg(i => (i - 1 + images.length) % images.length)} className="absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-white/90 p-1 shadow transition-colors hover:bg-white">
                    <ChevronLeft className="h-3.5 w-3.5" />
                  </button>
                  <button onClick={() => setCurrentImg(i => (i + 1) % images.length)} className="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-white/90 p-1 shadow transition-colors hover:bg-white">
                    <ChevronRight className="h-3.5 w-3.5" />
                  </button>
                  <div className="absolute bottom-2 left-1/2 flex -translate-x-1/2 gap-1 rounded-full bg-black/30 px-2 py-1 backdrop-blur">
                    {images.map((_, i) => (
                      <button key={i} onClick={() => setCurrentImg(i)} className={`rounded-full transition-all ${i === currentImg ? 'h-1.5 w-4 bg-white' : 'h-1.5 w-1.5 bg-white/60'}`} />
                    ))}
                  </div>
                </>
              )}
            </>
          ) : (
            <div className="h-full w-full bg-gradient-to-br from-emerald-100 to-teal-200" style={{ minHeight: 200 }} />
          )}
        </div>

        {/* Content */}
        <div className="flex flex-1 flex-col sm:flex-row gap-0">
          {/* Info */}
          <div className="flex-1 min-w-0 p-5">
            <div className="flex flex-wrap items-center gap-2 mb-1.5">
              {product.location && (
                <span className="inline-flex items-center gap-1 text-xs text-gray-400">
                  <MapPin className="h-3 w-3" />{product.location}
                </span>
              )}
              {product.instantConfirmation && (
                <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">
                  <Zap className="h-3 w-3" /> Instant Confirmation
                </span>
              )}
            </div>
            <h2 className="text-lg font-bold text-gray-900">{product.name}</h2>
            {product.highlights.length > 0 && (
              <ul className="mt-3 space-y-1.5">
                {product.highlights.slice(0, 4).map((h, i) => (
                  <li key={i} className="flex items-start gap-2 text-sm text-gray-600">
                    <Check className="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-emerald-500" />
                    <span>{h.length > 80 ? `${h.slice(0, 80)}…` : h}</span>
                  </li>
                ))}
              </ul>
            )}
          </div>

          {/* Price + CTA */}
          <div className="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center gap-4 px-5 pb-5 sm:py-5 sm:min-w-[160px] sm:border-l border-gray-100">
            {primaryVariant ? (
              <>
                <div className="sm:text-right">
                  <p className="text-xs text-gray-400">From</p>
                  <p className="text-xl font-bold text-gray-900">
                    {formatRupiah(grandTotal || primaryVariant.priceAdult)}
                  </p>
                  <p className="text-xs text-gray-400">Includes tax</p>
                </div>
                <button
                  onClick={() => handleSelect(primaryVariant)}
                  className={`flex-shrink-0 rounded-xl px-5 py-2.5 text-sm font-semibold transition-colors ${
                    selectedVariantId === primaryVariant.id
                      ? 'bg-emerald-600 text-white'
                      : 'bg-gray-900 text-white hover:bg-gray-700'
                  }`}
                >
                  {selectedVariantId === primaryVariant.id ? '✓ Selected' : 'Select'}
                </button>
              </>
            ) : (
              <span className="rounded-lg bg-red-50 px-3 py-2 text-xs font-medium text-red-500">Not available</span>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}

// ─── Skeleton ─────────────────────────────────────────────────────────────────
function CardSkeleton() {
  return (
    <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white animate-pulse flex">
      <div className="sm:w-56 flex-shrink-0 h-48 bg-gray-200" />
      <div className="flex-1 p-5 space-y-3">
        <div className="h-3 bg-gray-200 rounded w-1/4" />
        <div className="h-5 bg-gray-200 rounded w-1/2" />
        <div className="h-3 bg-gray-200 rounded w-3/4" />
        <div className="h-3 bg-gray-200 rounded w-2/3" />
      </div>
    </div>
  )
}

// ─── Cart Sidebar ─────────────────────────────────────────────────────────────
function CartSidebar() {
  const cart = useCartStore()
  const tickets = cart.tickets
  const total = cart.total()
  const subtotal = cart.subtotal()
  const voucher = cart.voucher

  return (
    <div className="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
      <div className="border-b border-gray-100 px-5 py-4">
        <h3 className="font-bold text-gray-900">My Order</h3>
      </div>

      <div className="p-5">
        {tickets.length === 0 ? (
          <div className="py-8 text-center">
            <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-50">
              <ShoppingCart className="h-5 w-5 text-gray-300" />
            </div>
            <p className="text-sm font-medium text-gray-500">No tickets selected</p>
            <p className="mt-1 text-xs text-gray-400">Click Select on the ticket you want</p>
          </div>
        ) : (
          <>
            <div className="space-y-4">
              {tickets.map((t) => {
                const lineTotal = t.qtyAdult * t.unitPriceAdult + t.qtyChild * t.unitPriceChild
                return (
                  <div key={t.variantId} className="flex items-start gap-2">
                    <button
                      onClick={() => cart.removeTicket(t.variantId)}
                      className="mt-0.5 flex-shrink-0 text-gray-300 transition-colors hover:text-red-400"
                    >
                      <X className="h-3.5 w-3.5" />
                    </button>
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-medium text-gray-800 leading-snug">
                        {cart.productName} – {t.variantLabel}
                      </p>
                      {cart.selectedDate && (
                        <p className="mt-0.5 text-xs text-gray-400">
                          {fmtDate(cart.selectedDate)} · {t.qtyAdult} Adult{t.qtyAdult !== 1 ? 's' : ''}{t.qtyChild > 0 ? `, ${t.qtyChild} Children` : ''}
                        </p>
                      )}
                    </div>
                    <p className="text-sm font-semibold text-gray-800 flex-shrink-0">{formatRupiah(lineTotal)}</p>
                  </div>
                )
              })}
            </div>

            {voucher && (
              <div className="mt-3 flex items-center justify-between rounded-lg bg-emerald-50 px-3 py-2 text-sm">
                <span className="flex items-center gap-1.5 font-medium text-emerald-700">
                  <Tag className="h-3.5 w-3.5" />{voucher.code}
                </span>
                <span className="font-semibold text-emerald-700">−{formatRupiah(voucher.discount)}</span>
              </div>
            )}

            <div className="mt-4 border-t border-gray-100 pt-4 space-y-1">
              {voucher && (
                <div className="flex justify-between text-xs text-gray-400">
                  <span>Subtotal</span><span>{formatRupiah(subtotal)}</span>
                </div>
              )}
              <div className="flex items-baseline justify-between">
                <p className="text-sm font-semibold text-gray-900">Total</p>
                <p className="text-xl font-bold text-gray-900">{formatRupiah(total)}</p>
              </div>
              <p className="text-right text-xs text-gray-400">Includes tax &amp; fees</p>
            </div>

            <div className="mt-4 space-y-2">
              <a
                href="/checkout"
                className="block w-full rounded-xl bg-emerald-600 py-3 text-center text-sm font-bold text-white transition-colors hover:bg-emerald-700"
              >
                Continue to Payment
              </a>
              <a
                href="/cart"
                className="block w-full rounded-xl border border-gray-200 py-2.5 text-center text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
              >
                View Cart
              </a>
            </div>
          </>
        )}
      </div>
    </div>
  )
}

// ─── Mobile Cart FAB ──────────────────────────────────────────────────────────
function MobileCartFab() {
  const cart = useCartStore()
  const count = cart.tickets.reduce((s, t) => s + t.qtyAdult + t.qtyChild, 0)
  if (count === 0) return null

  return (
    <a
      href="/checkout"
      className="lg:hidden fixed bottom-5 right-5 z-40 flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-3 rounded-full shadow-lg transition-colors"
    >
      <ShoppingCart className="w-4 h-4" />
      <span className="text-sm font-semibold">{count} ticket{count !== 1 ? 's' : ''} · {formatRupiah(cart.total())}</span>
    </a>
  )
}

// ─── Page ─────────────────────────────────────────────────────────────────────
export default function HomePage() {
  const [visitDate, setVisitDate] = useState(getTodayISO())
  const [adults, setAdults] = useState(1)
  const [childrenCount, setChildrenCount] = useState(0)

  useEffect(() => {
    const { tickets } = useCartStore.getState()
    if (tickets.length === 0) return
    tickets.forEach(ticket => {
      useCartStore.getState().setTicket({ ...ticket, qtyAdult: adults, qtyChild: childrenCount })
    })
  }, [adults, childrenCount])

  const { data: productsData, isLoading, isError } = useQuery({
    queryKey: ['products'],
    queryFn: () => api.get<ApiResponse<Product[]>>('/products'),
  })

  const { data: heroData } = useQuery({
    queryKey: ['hero-settings'],
    queryFn: () => api.get<{ data: HeroSettings }>('/settings/hero'),
    staleTime: 5 * 60 * 1000,
  })

  const products = productsData?.data ?? []
  const hero = heroData?.data
  const heroThumb = products[0]?.cloudinaryImageUrl ?? null

  return (
    <>
      <HeroSection hero={hero} thumb={heroThumb} />

      <SearchBar
        date={visitDate}
        onDateChange={setVisitDate}
        adults={adults}
        childCount={childrenCount}
        onAdults={setAdults}
        onChildren={setChildrenCount}
      />

      <main id="tickets" className="mx-auto max-w-5xl px-4 py-10">
        <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_290px] items-start">
          {/* Product list */}
          <div>
            <div className="mb-5 flex items-baseline justify-between gap-4">
              <div>
                <h2 className="text-xl font-bold text-gray-900">Available Tickets</h2>
                {!isLoading && products.length > 0 && (
                  <p className="mt-0.5 text-sm text-gray-500">
                    {products.length} available · {fmtDate(visitDate)}
                  </p>
                )}
              </div>
            </div>

            {isError && (
              <div className="text-center py-16 text-gray-400">
                <p className="font-medium">Failed to load products.</p>
                <p className="text-sm mt-1">Please refresh the page.</p>
              </div>
            )}

            {isLoading && (
              <div className="space-y-4">
                {Array.from({ length: 3 }).map((_, i) => <CardSkeleton key={i} />)}
              </div>
            )}

            {!isLoading && !isError && products.length === 0 && (
              <div className="text-center py-20 text-gray-400">
                <p className="font-medium">No products available yet.</p>
              </div>
            )}

            {!isLoading && products.length > 0 && (
              <div className="space-y-4">
                {products.map(p => (
                  <ProductCard
                    key={p.id}
                    product={p}
                    adults={adults}
                    childCount={childrenCount}
                    visitDate={visitDate}
                  />
                ))}
              </div>
            )}
          </div>

          {/* Sidebar */}
          <div className="hidden lg:block">
            <div className="sticky top-24">
              <CartSidebar />
            </div>
          </div>
        </div>
      </main>

      <MobileCartFab />
    </>
  )
}
