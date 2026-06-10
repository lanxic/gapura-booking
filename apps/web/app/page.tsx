'use client'

import { useState, useRef, useEffect } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { formatRupiah } from 'ui'
import { useCartStore } from '@/store/cart'
import type { Product, ProductVariant, ApiResponse } from '@/types'
import {
  Calendar, Users, ChevronDown, ChevronLeft, ChevronRight, Zap, ShoppingCart,
  Check, Tag, X, Image as ImageIcon, MapPin,
} from 'lucide-react'

type HeroSettings = {
  title: string
  subtitle: string
  image_url: string
  cta_label: string
  cta_url: string
  overlay_color: string
  overlay_opacity: number
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function getTodayISO() {
  return new Date().toISOString().split('T')[0]
}

function fmtDateShort(iso: string) {
  const d = new Date(iso + 'T00:00:00')
  return d.toLocaleDateString('en-GB', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' })
}

// ─── Hero ─────────────────────────────────────────────────────────────────────
function HeroSection({
  hero,
  thumbs,
}: {
  hero?: HeroSettings | null
  thumbs: string[]
}) {
  const mainImage = hero?.image_url ?? thumbs[0] ?? null
  const grid = mainImage ? thumbs.slice(1, 5) : thumbs.slice(0, 4)

  return (
    <section className="mx-auto max-w-6xl px-4 pt-6">
      <div className="flex flex-col gap-5">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div className="max-w-3xl">
            <div className="flex flex-wrap gap-2">
              <span className="rounded-full bg-sky-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-sky-700">
                Available Suites &amp; Villas
              </span>
              <span className="rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-semibold text-emerald-700">
                Ocean View
              </span>
              <span className="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-600">
                Luxury
              </span>
            </div>
            <div className="mt-3 flex items-center gap-1 text-amber-400">
              {Array.from({ length: 5 }).map((_, i) => (
                <span key={i} className="text-lg leading-none">★</span>
              ))}
            </div>
            <h1 className="mt-2 text-2xl font-semibold tracking-tight text-gray-900 sm:text-4xl">
              {hero?.title ?? 'Temukan pengalaman menginap terbaik'}
            </h1>
            <p className="mt-2 max-w-2xl text-sm text-gray-600 sm:text-base">
              {hero?.subtitle ?? 'Pilih kamar, villa, dan pengalaman yang paling pas untuk perjalanan Anda.'}
            </p>
          </div>

          {hero?.cta_label && (
            <a
              href={hero.cta_url || '#'}
              className="inline-flex items-center justify-center rounded-full bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-gray-700"
            >
              {hero.cta_label}
            </a>
          )}
        </div>

        <div className="grid gap-3 lg:grid-cols-[minmax(0,1.8fr)_minmax(260px,0.85fr)]">
          <div className="relative min-h-[320px] overflow-hidden rounded-[28px] bg-slate-100 shadow-[0_18px_50px_-30px_rgba(15,23,42,0.45)]">
            {mainImage ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img src={mainImage} alt="" className="h-full w-full object-cover" />
            ) : (
              <div className="h-full w-full bg-gradient-to-br from-slate-900 via-emerald-800 to-teal-500" />
            )}

            <div className="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-gray-700 shadow-sm backdrop-blur">
              1 night
            </div>

            <div className="absolute bottom-4 left-4 max-w-sm rounded-2xl bg-black/55 px-4 py-3 text-white backdrop-blur">
              <p className="text-[11px] uppercase tracking-[0.24em] text-white/70">
                Beachfront resort
              </p>
              <p className="mt-1 text-lg font-semibold">
                {hero?.title ?? 'Secana Beachtown Resort & Villas'}
              </p>
              <p className="mt-1 text-sm text-white/75">
                All-in experience, curated room options, and quick checkout.
              </p>
            </div>
          </div>

          <div className="relative grid grid-cols-2 grid-rows-2 gap-3">
            {grid.map((url, i) => (
              <div key={i} className="relative min-h-[154px] overflow-hidden rounded-[22px] bg-white shadow-[0_18px_40px_-26px_rgba(15,23,42,0.35)]">
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img src={url} alt="" className="h-full w-full object-cover" />
              </div>
            ))}
            {Array.from({ length: Math.max(0, 4 - grid.length) }).map((_, i) => (
              <div key={`empty-${i}`} className="min-h-[154px] rounded-[22px] bg-gradient-to-br from-slate-200 to-slate-100" />
            ))}

            <button className="absolute bottom-3 right-3 flex items-center gap-1.5 rounded-full border border-white/70 bg-white/90 px-3 py-1.5 text-xs font-medium text-gray-700 shadow-lg transition-colors hover:bg-white">
              <ImageIcon className="h-3.5 w-3.5" />
              Show All Photos
            </button>
          </div>
        </div>

        <div className="flex flex-wrap gap-2">
          {['Flexible dates', 'Instant confirmation', 'Best rate guarantee', 'Secure checkout'].map((label) => (
            <span
              key={label}
              className="rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 shadow-sm"
            >
              {label}
            </span>
          ))}
        </div>
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
  naked = false,
}: {
  adults: number
  childCount: number
  onAdults: (n: number) => void
  onChildren: (n: number) => void
  naked?: boolean
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

  const label = `${adults} Adult${adults !== 1 ? 's' : ''}, ${childCount} Child${childCount !== 1 ? 'ren' : ''}`

  return (
    <div className="relative z-20" ref={ref}>
      <button
        onClick={() => setOpen(o => !o)}
        className={naked
          ? 'flex items-center gap-2 h-full px-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors whitespace-nowrap'
          : 'flex items-center gap-2 h-9 px-3 border border-gray-300 rounded-lg bg-white text-sm text-gray-700 hover:border-emerald-400 transition-colors whitespace-nowrap'
        }
      >
        <Users className="w-4 h-4 text-gray-400" />
        <span>{label}</span>
        <ChevronDown className="w-3.5 h-3.5 text-gray-400" />
      </button>

      {open && (
        <div className="absolute left-0 top-full z-50 mt-2 w-72 rounded-2xl border border-gray-200 bg-white p-6 space-y-6 shadow-2xl">
          {/* Adults */}
          <div className="flex items-center justify-between">
            <div>
              <p className="text-base font-bold text-gray-900">Adult</p>
              <p className="text-sm text-gray-400">Ages 12 or above</p>
            </div>
            <div className="flex items-center gap-3">
              <button
                onClick={() => onAdults(Math.max(1, adults - 1))}
                className="w-9 h-9 flex items-center justify-center rounded-full border-2 border-gray-300 text-gray-600 text-lg font-medium hover:border-gray-900 hover:text-gray-900 transition-colors"
              >
                −
              </button>
              <span className="w-6 text-center text-base font-semibold text-gray-900">{adults}</span>
              <button
                onClick={() => onAdults(adults + 1)}
                className="w-9 h-9 flex items-center justify-center rounded-full border-2 border-gray-300 text-gray-600 text-lg font-medium hover:border-gray-900 hover:text-gray-900 transition-colors"
              >
                +
              </button>
            </div>
          </div>

          {/* Children */}
          <div className="flex items-center justify-between">
            <div>
              <p className="text-base font-bold text-gray-900">Children</p>
              <p className="text-sm text-gray-400">Ages 1 – 11</p>
            </div>
            <div className="flex items-center gap-3">
              <button
                onClick={() => onChildren(Math.max(0, childCount - 1))}
                className="w-9 h-9 flex items-center justify-center rounded-full border-2 border-gray-300 text-gray-600 text-lg font-medium hover:border-gray-900 hover:text-gray-900 transition-colors"
              >
                −
              </button>
              <span className="w-6 text-center text-base font-semibold text-gray-900">{childCount}</span>
              <button
                onClick={() => onChildren(childCount + 1)}
                className="w-9 h-9 flex items-center justify-center rounded-full border-2 border-gray-300 text-gray-600 text-lg font-medium hover:border-gray-900 hover:text-gray-900 transition-colors"
              >
                +
              </button>
            </div>
          </div>

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

// ─── Search / Filter Bar ──────────────────────────────────────────────────────
function SearchBar({
  date,
  onDateChange,
  adults,
  childCount,
  onAdults,
  onChildren,
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
    try {
      dateRef.current?.showPicker()
    } catch {
      dateRef.current?.click()
    }
  }

  return (
    <div className="mx-auto max-w-6xl px-4 pt-5">
      <div className="overflow-visible rounded-[24px] border border-white/70 bg-white/95 shadow-[0_18px_50px_-34px_rgba(15,23,42,0.35)] backdrop-blur">
        <div className="grid gap-px lg:grid-cols-[1.1fr_0.75fr_0.95fr_auto]">
          <div
            onClick={openDatePicker}
            className="flex cursor-pointer items-center gap-2 border-b border-gray-100 px-4 py-4 transition-colors hover:bg-gray-50 lg:border-b-0"
          >
            <Calendar className="h-4 w-4 flex-shrink-0 text-gray-400" />
            <div className="min-w-0">
              <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400">Check in</p>
              <p className="truncate text-sm font-medium text-gray-800 select-none">
                {new Date(date + 'T00:00:00').toLocaleDateString('en-GB', {
                  weekday: 'short', day: '2-digit', month: 'short', year: 'numeric',
                })}
              </p>
            </div>
            <input
              ref={dateRef}
              type="date"
              value={date}
              min={getTodayISO()}
              onChange={(e) => onDateChange(e.target.value)}
              className="sr-only"
            />
          </div>

          <div className="flex items-center border-b border-gray-100 px-4 py-4 lg:border-b-0 lg:border-l">
            <GuestPicker
              adults={adults}
              childCount={childCount}
              onAdults={onAdults}
              onChildren={onChildren}
              naked
            />
          </div>

          <div className="flex items-center gap-2 border-b border-gray-100 px-4 py-4 lg:border-b-0 lg:border-l">
            <Tag className="h-4 w-4 flex-shrink-0 text-gray-400" />
            <div className="min-w-0 flex-1">
              <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400">Promo code</p>
              <input
                type="text"
                placeholder="Kode Promo"
                className="w-full bg-transparent text-sm text-gray-700 outline-none placeholder:text-gray-400"
              />
            </div>
          </div>

          <button className="bg-gray-900 px-6 py-4 text-sm font-semibold text-white transition-colors hover:bg-gray-700 lg:rounded-r-[24px]">
            Update
          </button>
        </div>
      </div>

      <div className="mt-3 flex items-center justify-between gap-3 text-xs text-gray-500">
        <p>Total harga ditampilkan termasuk pajak &amp; biaya.</p>
        <button className="flex items-center gap-1.5 font-medium text-blue-600">
          <span>Tampilan Harga</span>
          <ChevronDown className="h-4 w-4" />
        </button>
      </div>
    </div>
  )
}

// ─── Product Card ─────────────────────────────────────────────────────────────
function ProductCard({
  product,
  adults,
  childCount,
  visitDate,
}: {
  product: Product
  adults: number
  childCount: number
  visitDate: string
}) {
  const [currentImg, setCurrentImg] = useState(0)
  const [expandedRate, setExpandedRate] = useState<string | null>(null)
  const [selectedVariantId, setSelectedVariantId] = useState<string | null>(null)
  const cart = useCartStore()

  function handleSelect(variant: ProductVariant) {
    cart.setProduct(product.slug, product.name)
    cart.setDate(visitDate, 'all-day')
    cart.setTicket({
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

  const images = [
    product.cloudinaryImageUrl,
    ...product.cloudinaryGalleryUrls,
  ].filter(Boolean) as string[]

  const activeVariants = product.variants.filter(v => v.isActive)
  const primaryVariant = activeVariants[0] ?? null
  const isExpanded = primaryVariant ? expandedRate === primaryVariant.id : false
  const badge = product.highlights[0] ?? null

  return (
    <div className="overflow-hidden rounded-[24px] border border-white/70 bg-white shadow-[0_18px_50px_-34px_rgba(15,23,42,0.35)]">
      <div className="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4">
        <div className="min-w-0">
          <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-gray-400">Room type</p>
          <h2 className="mt-1 text-lg font-semibold leading-tight text-gray-900">{product.name}</h2>
          <div className="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-500">
            {product.location && (
              <span className="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1">
                <MapPin className="h-3.5 w-3.5 text-gray-400" />
                {product.location}
              </span>
            )}
            <span className="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1">
              <Users className="h-3.5 w-3.5 text-gray-400" />
              {adults} Adult{adults !== 1 ? 's' : ''}{childCount > 0 ? `, ${childCount} Child${childCount !== 1 ? 'ren' : ''}` : ''}
            </span>
            {product.instantConfirmation && (
              <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700">
                <Zap className="h-3.5 w-3.5" />
                Instant confirmation
              </span>
            )}
          </div>
        </div>
        {badge && (
          <span className="flex-shrink-0 rounded-full bg-gray-900 px-3 py-1 text-xs font-medium text-white">
            {badge.length > 34 ? `${badge.slice(0, 34)}…` : badge}
          </span>
        )}
      </div>

      <div className="grid gap-4 px-5 py-5 lg:grid-cols-[220px_minmax(0,1fr)_180px] lg:items-start">
        <div className="space-y-3">
          <div className="relative overflow-hidden rounded-[18px] bg-gray-100" style={{ aspectRatio: '4 / 3' }}>
            {images.length > 0 ? (
              <>
                <div
                  className="flex h-full transition-transform duration-300 ease-out"
                  style={{
                    width: `${images.length * 100}%`,
                    transform: `translateX(-${(currentImg / images.length) * 100}%)`,
                  }}
                >
                  {images.map((url, i) => (
                    <div key={i} className="h-full flex-shrink-0" style={{ width: `${100 / images.length}%` }}>
                      {/* eslint-disable-next-line @next/next/no-img-element */}
                      <img src={url} alt={product.name} className="h-full w-full object-cover" />
                    </div>
                  ))}
                </div>

                {images.length > 1 && (
                  <>
                    <button
                      onClick={() => setCurrentImg(i => (i - 1 + images.length) % images.length)}
                      className="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-white/90 p-1.5 text-gray-700 shadow transition-colors hover:bg-white"
                    >
                      <ChevronLeft className="h-4 w-4" />
                    </button>
                    <button
                      onClick={() => setCurrentImg(i => (i + 1) % images.length)}
                      className="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-white/90 p-1.5 text-gray-700 shadow transition-colors hover:bg-white"
                    >
                      <ChevronRight className="h-4 w-4" />
                    </button>
                    <div className="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-1.5 rounded-full bg-black/35 px-2 py-1 backdrop-blur">
                      {images.map((_, i) => (
                        <button
                          key={i}
                          onClick={() => setCurrentImg(i)}
                          className={`rounded-full transition-all ${
                            i === currentImg ? 'h-1.5 w-4 bg-white' : 'h-1.5 w-1.5 bg-white/60'
                          }`}
                        />
                      ))}
                    </div>
                  </>
                )}
              </>
            ) : (
              <div className="h-full w-full bg-gradient-to-br from-emerald-100 to-teal-200" />
            )}
          </div>

          <div className="grid grid-cols-3 gap-2 text-[11px] text-gray-500">
            <div className="rounded-2xl bg-gray-50 px-2.5 py-2 text-center">
              <p className="font-semibold text-gray-900">{images.length || 1}</p>
              <p>Photos</p>
            </div>
            <div className="rounded-2xl bg-gray-50 px-2.5 py-2 text-center">
              <p className="font-semibold text-gray-900">{activeVariants.length}</p>
              <p>Rates</p>
            </div>
            <div className="rounded-2xl bg-gray-50 px-2.5 py-2 text-center">
              <p className="font-semibold text-gray-900">Last minute</p>
              <p>Deal</p>
            </div>
          </div>
        </div>

        <div className="space-y-3">
          <div>
            <p className="text-sm font-semibold text-gray-900">Last Minute</p>
            <div className="mt-2 space-y-1.5">
              <div className="flex items-center gap-2 text-sm text-gray-700">
                <Check className="h-3.5 w-3.5 flex-shrink-0 text-emerald-500" />
                Include breakfast
              </div>
              <div className="flex items-center gap-2 text-sm text-gray-700">
                <Check className="h-3.5 w-3.5 flex-shrink-0 text-gray-400" />
                Pay now
              </div>
              <div className="flex items-center gap-2 text-sm text-gray-700">
                <Check className="h-3.5 w-3.5 flex-shrink-0 text-gray-400" />
                Non-refundable
              </div>
              <div className="flex items-center gap-2 text-sm text-gray-700">
                <Check className="h-3.5 w-3.5 flex-shrink-0 text-gray-400" />
                Non-reschedulable
              </div>
            </div>
          </div>

          {product.highlights.length > 1 && (
            <div>
              <p className="text-sm font-semibold text-gray-900">Extra Benefit</p>
              <div className="mt-2 space-y-1.5">
                {product.highlights.slice(1, 4).map((h, i) => (
                  <div key={i} className="flex items-start gap-2 text-sm text-gray-600">
                    <span className="mt-1 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-emerald-500" />
                    <span>{h.length > 70 ? `${h.slice(0, 70)}…` : h}</span>
                  </div>
                ))}
              </div>
            </div>
          )}

          {primaryVariant?.description && (
            <button
              onClick={() => setExpandedRate(isExpanded ? null : primaryVariant.id)}
              className="text-sm font-medium text-blue-600 hover:text-blue-700"
            >
              {isExpanded ? 'Hide details' : 'Rate Details'}
            </button>
          )}

          {primaryVariant?.description && isExpanded && (
            <p className="text-sm leading-relaxed text-gray-600">{primaryVariant.description}</p>
          )}
        </div>

        <div className="flex flex-col items-stretch rounded-[20px] border border-gray-200 bg-gray-50 p-4 lg:min-h-[100%]">
          {primaryVariant ? (
            <>
              <p className="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Total price for 1 night</p>
              {(() => {
                const totalAdult = primaryVariant.priceAdult * adults
                const totalChild = primaryVariant.priceChild > 0 ? primaryVariant.priceChild * childCount : 0
                const grandTotal = totalAdult + totalChild

                return (
                  <>
                    <p className="mt-2 text-2xl font-semibold tracking-tight text-gray-900">
                      {formatRupiah(grandTotal > 0 ? grandTotal : primaryVariant.priceAdult)}
                    </p>
                    <p className="mt-1 text-xs text-gray-500">Includes Taxes &amp; Fees</p>
                    <button
                      onClick={() => handleSelect(primaryVariant)}
                      className={`mt-4 rounded-full px-4 py-2.5 text-sm font-semibold transition-colors ${
                        selectedVariantId === primaryVariant.id
                          ? 'bg-emerald-600 text-white hover:bg-emerald-700'
                          : 'bg-gray-900 text-white hover:bg-gray-700'
                      }`}
                    >
                      {selectedVariantId === primaryVariant.id ? 'Selected' : 'Select'}
                    </button>
                    {activeVariants.length > 1 && (
                      <p className="mt-3 text-center text-xs text-red-500">Our last 1 room</p>
                    )}
                  </>
                )
              })()}
            </>
          ) : (
            <div className="flex h-full items-center justify-center rounded-[16px] bg-rose-50 p-4 text-center text-sm text-rose-700">
              Tidak ada tiket tersedia saat ini.
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

// ─── Skeleton ─────────────────────────────────────────────────────────────────
function CardSkeleton() {
  return (
    <div className="bg-white border border-gray-200 rounded-xl overflow-hidden animate-pulse">
      <div className="px-5 pt-4 pb-3 flex items-center gap-3">
        <div className="h-5 bg-gray-200 rounded w-1/3" />
        <div className="h-5 bg-gray-200 rounded-full w-24 ml-auto" />
      </div>
      <div className="flex border-t border-gray-100">
        <div className="w-64 flex-shrink-0 border-r border-gray-100">
          <div className="h-44 bg-gray-200" />
          <div className="p-4 space-y-2">
            <div className="h-3 bg-gray-200 rounded w-3/4" />
            <div className="h-3 bg-gray-200 rounded w-1/2" />
          </div>
        </div>
        <div className="flex-1 p-5 space-y-3">
          <div className="h-4 bg-gray-200 rounded w-1/4" />
          <div className="h-3 bg-gray-200 rounded w-1/2" />
          <div className="h-3 bg-gray-200 rounded w-2/5" />
          <div className="flex justify-end mt-6">
            <div className="h-9 bg-gray-200 rounded-lg w-28" />
          </div>
        </div>
      </div>
    </div>
  )
}

// ─── Cart Sidebar ─────────────────────────────────────────────────────────────
function CartSidebar({ promoImage }: { promoImage?: string | null }) {
  const cart = useCartStore()
  const tickets = cart.tickets
  const total = cart.total()
  const subtotal = cart.subtotal()
  const voucher = cart.voucher

  return (
    <div className="space-y-4">
      <div className="overflow-hidden rounded-[24px] border border-white/70 bg-white shadow-[0_18px_50px_-34px_rgba(15,23,42,0.35)]">
        <div className="relative h-48 bg-slate-100">
          {promoImage ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img src={promoImage} alt="Promo getaway" className="h-full w-full object-cover" />
          ) : (
            <div className="h-full w-full bg-gradient-to-br from-slate-900 via-emerald-700 to-teal-500" />
          )}
          <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/15 to-transparent" />
          <div className="absolute bottom-0 left-0 right-0 p-4 text-white">
            <p className="text-[11px] uppercase tracking-[0.24em] text-white/70">Summer getaway</p>
            <h3 className="mt-1 text-lg font-semibold">Room with Breakfast</h3>
            <p className="mt-1 text-xs text-white/75">Stay flexible and book the best available rate.</p>
          </div>
        </div>
      </div>

      <div className="rounded-[24px] border border-white/70 bg-white shadow-[0_18px_50px_-34px_rgba(15,23,42,0.35)]">
        <div className="p-5">
          <h3 className="mb-4 text-lg font-semibold text-gray-900">Your Booking</h3>

          {tickets.length === 0 ? (
            <div className="py-6 text-center">
              <div className="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-gray-100">
                <ShoppingCart className="h-5 w-5 text-gray-300" />
              </div>
              <p className="text-sm font-medium text-gray-500">Belum ada tiket dipilih</p>
              <p className="mt-1 text-xs text-gray-400">Klik Select pada tiket yang diinginkan</p>
            </div>
          ) : (
            <>
              {cart.productName && (
                <p className="mb-3 text-sm font-semibold text-gray-500">{cart.productName}</p>
              )}

              <div className="space-y-4">
                {tickets.map((t) => {
                  const lineTotal = t.qtyAdult * t.unitPriceAdult + t.qtyChild * t.unitPriceChild
                  return (
                    <div key={t.variantId}>
                      <div className="mb-0.5 flex items-start gap-2">
                        <button
                          onClick={() => cart.removeTicket(t.variantId)}
                          className="mt-0.5 flex-shrink-0 text-gray-300 transition-colors hover:text-red-400"
                        >
                          <X className="h-3.5 w-3.5" />
                        </button>
                        <p className="text-sm font-medium leading-snug text-gray-800">
                          {cart.productName} – {t.variantLabel}
                        </p>
                      </div>
                      {cart.selectedDate && (
                        <p className="ml-5 text-xs text-gray-400">
                          {fmtDateShort(cart.selectedDate)},&nbsp;
                          {t.qtyAdult} Adult{t.qtyAdult !== 1 ? 's' : ''}
                          {t.qtyChild > 0 ? `, ${t.qtyChild} Child${t.qtyChild !== 1 ? 'ren' : ''}` : ''}
                        </p>
                      )}
                      <div className="mt-1 ml-5 flex items-center justify-between">
                        <p className="text-xs text-gray-500">{t.qtyAdult + t.qtyChild} Tiket × 1 Hari</p>
                        <p className="text-sm font-semibold text-gray-800">{formatRupiah(lineTotal)}</p>
                      </div>
                    </div>
                  )
                })}
              </div>

              {voucher && (
                <div className="mt-3 flex items-center justify-between text-sm">
                  <span className="flex items-center gap-1.5 text-emerald-600">
                    <Tag className="h-3.5 w-3.5" />{voucher.code}
                  </span>
                  <span className="font-semibold text-emerald-600">−{formatRupiah(voucher.discount)}</span>
                </div>
              )}

              <div className="mt-4 border-t border-gray-200 pt-4">
                {voucher && (
                  <div className="mb-1 flex justify-between text-xs text-gray-400">
                    <span>Subtotal</span><span>{formatRupiah(subtotal)}</span>
                  </div>
                )}
                <div className="flex items-baseline justify-between">
                  <p className="font-bold text-gray-900">Total</p>
                  <p className="text-xl font-bold text-gray-900">{formatRupiah(total)}</p>
                </div>
                <p className="mt-0.5 text-right text-xs text-gray-400">Includes Taxes &amp; Fees</p>
              </div>

              <div className="mt-4 flex gap-2">
                <a
                  href="/cart"
                  className="flex-1 rounded-full border-2 border-gray-900 py-2.5 text-center text-sm font-bold text-gray-900 transition-colors hover:bg-gray-50"
                >
                  View Cart
                </a>
                <a
                  href="/checkout"
                  className="flex-1 rounded-full bg-gray-900 py-2.5 text-center text-sm font-bold text-white transition-colors hover:bg-gray-700"
                >
                  Book Now
                </a>
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  )
}

// ─── Page ─────────────────────────────────────────────────────────────────────
export default function HomePage() {
  const [visitDate, setVisitDate] = useState(getTodayISO())
  const [adults, setAdults] = useState(1)
  const [childrenCount, setChildrenCount] = useState(0)

  // Sync cart ticket quantities whenever guest count changes
  useEffect(() => {
    const { tickets } = useCartStore.getState()
    if (tickets.length === 0) return
    tickets.forEach(ticket => {
      useCartStore.getState().setTicket({
        ...ticket,
        qtyAdult: adults,
        qtyChild: childrenCount,
      })
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
  const heroPreviewImage = hero?.image_url ?? null

  // Collect product images for hero grid thumbnails
  const productThumbs = products
    .flatMap(p => [
      p.cloudinaryThumbnailUrl,
      p.cloudinaryImageUrl,
      ...(p.cloudinaryGalleryUrls ?? []),
    ])
    .filter(Boolean)
    .slice(0, 4) as string[]

  return (
    <>
      {/* ── Hero ── */}
      <HeroSection hero={hero} thumbs={productThumbs} />

      {/* ── Search bar ── */}
      <SearchBar
        date={visitDate}
        onDateChange={setVisitDate}
        adults={adults}
        childCount={childrenCount}
        onAdults={setAdults}
        onChildren={setChildrenCount}
      />

      <div className="mx-auto max-w-6xl px-4 pt-4">
        <div className="flex flex-wrap gap-2">
          {['Suites & Villas', 'Packages', 'Spa', 'Dining Experiences', 'Transport'].map((label) => (
            <span
              key={label}
              className="rounded-full border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600 shadow-sm"
            >
              {label}
            </span>
          ))}
        </div>
      </div>

      {/* ── Main content ── */}
      <div className="mx-auto max-w-6xl px-4 py-6">
        <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_300px] items-start">

          {/* ── Product list ── */}
          <div className="flex-1 min-w-0">
            {/* Section header */}
            <div className="mb-4 flex items-end justify-between gap-4">
              <div>
                <h2 className="text-lg font-semibold text-gray-900 sm:text-xl">Pilihan Tiket</h2>
                {!isLoading && products.length > 0 && (
                  <p className="mt-0.5 text-xs text-gray-500">
                    {products.length} produk tersedia · {fmtDateShort(visitDate)}
                  </p>
                )}
              </div>
              <p className="hidden text-xs text-gray-400 sm:block">Browse like a resort storefront</p>
            </div>

            {/* Error */}
            {isError && (
              <div className="text-center py-16 text-gray-400">
                <p className="font-medium">Gagal memuat produk.</p>
                <p className="text-sm mt-1">Silakan refresh halaman.</p>
              </div>
            )}

            {/* Loading */}
            {isLoading && (
              <div className="space-y-4">
                {Array.from({ length: 3 }).map((_, i) => <CardSkeleton key={i} />)}
              </div>
            )}

            {/* Empty */}
            {!isLoading && !isError && products.length === 0 && (
              <div className="text-center py-20 text-gray-400">
                <p className="font-medium">Belum ada produk tersedia.</p>
              </div>
            )}

            {/* Products */}
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

          {/* ── Sidebar ── */}
          <div className="hidden lg:block">
            <div className="sticky top-24">
              <CartSidebar promoImage={heroPreviewImage ?? productThumbs[0] ?? null} />
            </div>
          </div>

        </div>
      </div>

      {/* ── Mobile cart FAB ── */}
      <MobileCartFab />
    </>
  )
}

// ─── Mobile cart floating button ─────────────────────────────────────────────
function MobileCartFab() {
  const cart = useCartStore()
  const count = cart.tickets.reduce((s, t) => s + t.qtyAdult + t.qtyChild, 0)
  if (count === 0) return null

  return (
    <a
      href="/cart"
      className="lg:hidden fixed bottom-5 right-5 z-40 flex items-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white px-4 py-3 rounded-full shadow-lg transition-colors"
    >
      <ShoppingCart className="w-4 h-4" />
      <span className="text-sm font-semibold">{count} tiket · {formatRupiah(cart.total())}</span>
    </a>
  )
}
