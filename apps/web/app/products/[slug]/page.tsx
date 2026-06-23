'use client'

import { use, useState, useRef } from 'react'
import { useQuery } from '@tanstack/react-query'
import { useRouter } from 'next/navigation'
import { api } from '@/lib/api'
import { cn, formatRupiah } from 'ui'
import { useCartStore } from '@/store/cart'
import type { Product, AvailabilitySlot, ApiResponse, ProductVariant } from '@/types'
import {
  ChevronLeft, ChevronRight, Calendar, Plus, Minus,
  ChevronDown, ChevronUp, MapPin, Clock, AlertCircle,
  Share2,
} from 'lucide-react'

const DAYS_ID = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']
const MONTHS_ID = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
]

function getTodayISO() {
  return new Date().toISOString().split('T')[0]
}

function getTomorrowISO() {
  const d = new Date()
  d.setDate(d.getDate() + 1)
  return d.toISOString().split('T')[0]
}

type SlotMap = Record<string, AvailabilitySlot[]>

// ─── Image Carousel ───────────────────────────────────────────────────────────
function ImageCarousel({ images, name }: { images: string[]; name: string }) {
  const [current, setCurrent] = useState(0)
  const all = images.length > 0 ? images : []
  if (all.length === 0) {
    return (
      <div className="w-full h-72 bg-gradient-to-br from-emerald-100 to-teal-200 flex items-center justify-center">
        <Calendar className="w-16 h-16 text-emerald-400" />
      </div>
    )
  }
  return (
    <div className="relative">
      <div className="w-full h-72 overflow-hidden relative">
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img src={all[current]} alt={name} className="w-full h-full object-cover" />
        {all.length > 1 && (
          <>
            <button
              onClick={() => setCurrent((c) => (c - 1 + all.length) % all.length)}
              className="absolute left-3 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white rounded-full p-1.5 shadow transition-colors"
            >
              <ChevronLeft className="w-5 h-5 text-gray-700" />
            </button>
            <button
              onClick={() => setCurrent((c) => (c + 1) % all.length)}
              className="absolute right-3 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white rounded-full p-1.5 shadow transition-colors"
            >
              <ChevronRight className="w-5 h-5 text-gray-700" />
            </button>
          </>
        )}
      </div>
      {all.length > 1 && (
        <div className="flex gap-2 px-4 pt-2 overflow-x-auto pb-1">
          {all.map((url, i) => (
            <button
              key={i}
              onClick={() => setCurrent(i)}
              className={cn(
                'flex-shrink-0 w-16 h-12 rounded overflow-hidden border-2 transition-all',
                i === current ? 'border-emerald-600' : 'border-transparent opacity-70 hover:opacity-100',
              )}
            >
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={url} alt="" className="w-full h-full object-cover" />
            </button>
          ))}
        </div>
      )}
    </div>
  )
}

// ─── Mini Calendar ────────────────────────────────────────────────────────────
function MiniCalendar({
  slotsByDate,
  selectedDate,
  onSelectDate,
}: {
  slotsByDate: SlotMap
  selectedDate: string
  onSelectDate: (d: string) => void
}) {
  const today = new Date()
  const [year, setYear] = useState(today.getFullYear())
  const [month, setMonth] = useState(today.getMonth())

  const firstDay = new Date(year, month, 1).getDay()
  const mondayOffset = (firstDay === 0 ? 6 : firstDay - 1)
  const daysInMonth = new Date(year, month + 1, 0).getDate()
  const todayStr = getTodayISO()

  const cells: (number | null)[] = [
    ...Array(mondayOffset).fill(null),
    ...Array.from({ length: daysInMonth }, (_, i) => i + 1),
  ]

  const prevMonth = () => {
    if (month === 0) { setMonth(11); setYear(y => y - 1) }
    else setMonth(m => m - 1)
  }
  const nextMonth = () => {
    if (month === 11) { setMonth(0); setYear(y => y + 1) }
    else setMonth(m => m + 1)
  }

  return (
    <div className="border border-gray-200 rounded-xl p-4 bg-white">
      {/* Month header */}
      <div className="flex items-center justify-between mb-3">
        <button onClick={prevMonth} className="p-1 rounded hover:bg-gray-100 transition-colors">
          <ChevronLeft className="w-4 h-4" />
        </button>
        <span className="font-semibold text-sm text-gray-800">
          {MONTHS_ID[month]} {year}
        </span>
        <button onClick={nextMonth} className="p-1 rounded hover:bg-gray-100 transition-colors">
          <ChevronRight className="w-4 h-4" />
        </button>
      </div>
      {/* Day headers */}
      <div className="grid grid-cols-7 mb-1">
        {DAYS_ID.map(d => (
          <div key={d} className="text-center text-xs font-medium text-gray-400 py-1">{d}</div>
        ))}
      </div>
      {/* Day cells */}
      <div className="grid grid-cols-7">
        {cells.map((day, i) => {
          if (!day) return <div key={i} />
          const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`
          const daySlots = slotsByDate[dateStr] ?? []
          const hasAvail = daySlots.some(s => s.status === 'available' || s.status === 'limited')
          const isPast = dateStr < todayStr
          const isSelected = dateStr === selectedDate
          const isToday = dateStr === todayStr

          return (
            <button
              key={i}
              disabled={isPast || (!hasAvail && daySlots.length === 0)}
              onClick={() => onSelectDate(dateStr)}
              className={cn(
                'relative flex flex-col items-center py-1.5 rounded-lg text-sm transition-all',
                isSelected
                  ? 'bg-emerald-600 text-white'
                  : isToday
                  ? 'bg-emerald-50 text-emerald-700 font-semibold'
                  : isPast
                  ? 'text-gray-300 cursor-not-allowed'
                  : hasAvail
                  ? 'hover:bg-emerald-50 text-gray-700 cursor-pointer'
                  : 'text-gray-400 cursor-not-allowed',
              )}
            >
              {day}
              {hasAvail && !isSelected && (
                <span className="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-emerald-500" />
              )}
            </button>
          )
        })}
      </div>
      {/* Legend */}
      <div className="flex items-center gap-4 mt-3 pt-3 border-t border-gray-100 text-xs text-gray-500">
        <span className="flex items-center gap-1">
          <span className="w-2 h-2 rounded-full bg-emerald-500 inline-block" /> Tersedia
        </span>
        <span className="flex items-center gap-1">
          <span className="w-2 h-2 rounded-full bg-amber-400 inline-block" /> Terbatas
        </span>
        <span className="flex items-center gap-1">
          <span className="w-2 h-2 rounded-full bg-red-400 inline-block" /> Habis
        </span>
      </div>
    </div>
  )
}

// ─── Variant Detail Expand ────────────────────────────────────────────────────
function VariantDetailExpand({ variant, product }: { variant: ProductVariant; product: Product }) {
  const [open, setOpen] = useState(false)
  return (
    <div className="border-t border-gray-100 mt-3 pt-3">
      <button
        onClick={() => setOpen(o => !o)}
        className="flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900 transition-colors"
      >
        View Details &amp; Includes
        {open ? <ChevronUp className="w-4 h-4" /> : <ChevronDown className="w-4 h-4" />}
      </button>
      {open && (
        <div className="mt-3 space-y-4 text-sm">
          {variant.description && (
            <div>
              <p className="font-semibold text-gray-700 mb-1">Description</p>
              <p className="text-gray-600">{variant.description}</p>
            </div>
          )}
          {product.usageInstructions && (
            <div>
              <p className="font-semibold text-gray-700 mb-1">How to use</p>
              <ul className="list-disc list-inside text-gray-600 space-y-0.5">
                {product.usageInstructions.split('\n').filter(Boolean).map((line, i) => (
                  <li key={i}>{line.replace(/^[-•]\s*/, '')}</li>
                ))}
              </ul>
            </div>
          )}
          {product.cancellationPolicy && (
            <div>
              <p className="font-semibold text-gray-700 mb-1">Cancellation</p>
              <p className="text-gray-600">{product.cancellationPolicy}</p>
            </div>
          )}
          {product.termsConditions && (
            <div>
              <p className="font-semibold text-gray-700 mb-1">Terms &amp; Conditions</p>
              <ul className="list-disc list-inside text-gray-600 space-y-0.5">
                {product.termsConditions.split('\n').filter(Boolean).map((line, i) => (
                  <li key={i}>{line.replace(/^[-•]\s*/, '')}</li>
                ))}
              </ul>
            </div>
          )}
        </div>
      )}
    </div>
  )
}

// ─── Main Page ────────────────────────────────────────────────────────────────
export default function ProductDetailPage({
  params,
}: {
  params: Promise<{ slug: string }>
}) {
  const { slug } = use(params)
  const router = useRouter()
  const cart = useCartStore()

  const today = getTodayISO()
  const tomorrow = getTomorrowISO()

  const [activeTab, setActiveTab] = useState<'deskripsi' | 'info' | 'jam' | 'lokasi'>('deskripsi')
  const [selectedDate, setSelectedDate] = useState<string>(today)
  const [showDatePicker, setShowDatePicker] = useState(false)
  const [selectedVariantId, setSelectedVariantId] = useState<string | null>(null)
  const [selectedSlotId, setSelectedSlotId] = useState<string | null>(null)
  const [qtyAdult, setQtyAdult] = useState(1)
  const [qtyChild, setQtyChild] = useState(0)

  const availRef = useRef<HTMLDivElement>(null)

  const { data: productData, isLoading: loadingProduct } = useQuery({
    queryKey: ['product', slug],
    queryFn: () => api.get<ApiResponse<Product>>(`/products/${slug}`),
  })

  const { data: availData, isLoading: loadingAvail } = useQuery({
    queryKey: ['availability', slug, today],
    queryFn: () =>
      api.get<ApiResponse<AvailabilitySlot[]>>(
        `/products/${slug}/availability?from=${today}`,
      ),
    enabled: !!slug,
  })

  const { data: settingsData } = useQuery({
    queryKey: ['settings', 'general'],
    queryFn: () => api.get<{ data: { app_name?: string | null } }>('/settings/general'),
    staleTime: 5 * 60 * 1000,
  })

  const appName = settingsData?.data?.app_name || 'Amartha eTicket'

  const product = productData?.data
  const slots: AvailabilitySlot[] = availData?.data ?? []

  const slotsByDate: SlotMap = slots.reduce<SlotMap>((acc, slot) => {
    if (!acc[slot.date]) acc[slot.date] = []
    acc[slot.date].push(slot)
    return acc
  }, {})

  const slotsForDate = slotsByDate[selectedDate] ?? []
  const selectedVariant = product?.variants.find((v) => v.id === selectedVariantId)

  function handleSelectDate(date: string) {
    setSelectedDate(date)
    setShowDatePicker(false)
    setSelectedSlotId(null)
  }

  function handleSelectVariant(variantId: string) {
    if (selectedVariantId === variantId) {
      setSelectedVariantId(null)
      setSelectedSlotId(null)
    } else {
      setSelectedVariantId(variantId)
      setSelectedSlotId(null)
      setQtyAdult(1)
      setQtyChild(0)
      setTimeout(() => availRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100)
    }
  }

  function handleAddToCart() {
    if (!product || !selectedVariantId || !selectedDate) return
    if (!selectedVariant) return

    const slotId = slotsForDate.length > 0
      ? (selectedSlotId ?? slotsForDate[0].id)
      : 'all-day'

    cart.setProduct(product.slug)
    cart.setDate(selectedDate, slotId)
    cart.setTicket({
      productId: product.id,
      variantId: selectedVariantId,
      variantLabel: selectedVariant.label,
      qtyAdult,
      qtyChild,
      unitPriceAdult: selectedVariant.priceAdult,
      unitPriceChild: selectedVariant.priceChild,
      addons: [],
    })

    router.push('/cart')
  }

  const totalPrice = selectedVariant
    ? qtyAdult * selectedVariant.priceAdult + qtyChild * selectedVariant.priceChild
    : 0

  const canAddToCart = !!selectedVariantId && !!selectedDate && (qtyAdult + qtyChild) > 0 && slotsForDate.length > 0

  if (loadingProduct) {
    return (
      <div className="animate-pulse">
        <div className="h-72 bg-gray-200" />
        <div className="max-w-4xl mx-auto px-4 py-6 space-y-4">
          <div className="h-6 bg-gray-200 rounded w-1/2" />
          <div className="h-4 bg-gray-200 rounded w-3/4" />
        </div>
      </div>
    )
  }

  if (!product) {
    return (
      <div className="max-w-4xl mx-auto px-4 py-20 text-center text-gray-500">
        <p className="text-lg font-medium">Product not found.</p>
        <a href="/" className="text-emerald-600 hover:underline text-sm mt-2 inline-block">
          Back to products
        </a>
      </div>
    )
  }

  const galleryImages = product.cloudinaryGalleryUrls.length > 0
    ? product.cloudinaryGalleryUrls
    : product.cloudinaryImageUrl
    ? [product.cloudinaryImageUrl]
    : []

  const tabs = [
    { key: 'deskripsi' as const, label: 'Description' },
    { key: 'info' as const, label: 'Important Information' },
    { key: 'jam' as const, label: 'Opening Hours' },
    { key: 'lokasi' as const, label: 'Location' },
  ]

  return (
    <div>
      {/* Breadcrumb */}
      <div className="max-w-4xl mx-auto px-4 pt-4 pb-2 text-sm text-gray-500">
        <a href="/" className="hover:text-emerald-600 transition-colors">Home</a>
        <span className="mx-2">&gt;</span>
        <span className="text-gray-700">{product.name}</span>
      </div>

      {/* Image Carousel */}
      <div className="max-w-4xl mx-auto px-4">
        <ImageCarousel images={galleryImages} name={product.name} />
      </div>

      <div className="max-w-4xl mx-auto px-4 py-6">
        {/* Title + Share */}
        <div className="mb-1">
          <p className="text-xs text-gray-400 mb-1">{appName}</p>
          <div className="flex items-start justify-between gap-4">
            <h1 className="text-2xl font-bold text-gray-900">{product.name}</h1>
            <button
              onClick={() => {
                if (navigator.share) navigator.share({ title: product.name, url: window.location.href })
              }}
              className="flex-shrink-0 p-2 rounded-full border border-gray-200 hover:bg-gray-50 transition-colors text-gray-500"
            >
              <Share2 className="w-4 h-4" />
            </button>
          </div>
        </div>

        {/* Tabs */}
        <div className="flex gap-0 border-b border-gray-200 mb-6 overflow-x-auto mt-4">
          {tabs.map(tab => (
            <button
              key={tab.key}
              onClick={() => setActiveTab(tab.key)}
              className={cn(
                'flex-shrink-0 px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors whitespace-nowrap',
                activeTab === tab.key
                  ? 'border-emerald-600 text-emerald-700 bg-emerald-50/50'
                  : 'border-transparent text-gray-500 hover:text-gray-700',
              )}
            >
              {tab.label}
            </button>
          ))}
        </div>

        {/* Tab Content */}
        {activeTab === 'deskripsi' && (
          <div className="space-y-5 mb-8">
            {product.highlights.length > 0 && (
              <div>
                <h2 className="font-bold text-emerald-700 mb-2">Highlights</h2>
                <ul className="space-y-1">
                  {product.highlights.map((h, i) => (
                    <li key={i} className="flex items-start gap-2 text-sm text-gray-700">
                      <span className="mt-1 w-1.5 h-1.5 rounded-full bg-emerald-500 flex-shrink-0" />
                      {h}
                    </li>
                  ))}
                </ul>
              </div>
            )}
            {product.description && (
              <p className="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{product.description}</p>
            )}
          </div>
        )}

        {activeTab === 'info' && (
          <div className="space-y-4 mb-8 text-sm text-gray-700">
            {product.termsConditions ? (
              <div className="whitespace-pre-line">{product.termsConditions}</div>
            ) : (
              <p className="text-gray-400">No information available.</p>
            )}
          </div>
        )}

        {activeTab === 'jam' && (
          <div className="mb-8 text-sm text-gray-700">
            {product.openingHours ? (
              <div className="flex items-start gap-3">
                <Clock className="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" />
                <p>{product.openingHours}</p>
              </div>
            ) : (
              <p className="text-gray-400">Opening hours information not yet available.</p>
            )}
          </div>
        )}

        {activeTab === 'lokasi' && (
          <div className="mb-8 text-sm text-gray-700">
            {product.location ? (
              <div className="flex items-start gap-3">
                <MapPin className="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" />
                <p className="whitespace-pre-line">{product.location}</p>
              </div>
            ) : (
              <p className="text-gray-400">Location information not yet available.</p>
            )}
          </div>
        )}

        {/* Periksa Ketersediaan */}
        <div ref={availRef} className="mb-6">
          <h2 className="font-bold text-gray-800 mb-3 flex items-center gap-2">
            <span className="text-emerald-600">&#10148;</span> Check Availability
          </h2>
          <div className="flex gap-2 flex-wrap">
            <button
              onClick={() => handleSelectDate(today)}
              className={cn(
                'px-4 py-2 rounded-lg border text-sm font-medium transition-all',
                selectedDate === today
                  ? 'bg-emerald-700 text-white border-emerald-700'
                  : 'border-gray-300 text-gray-700 hover:border-emerald-500',
              )}
            >
              Today
            </button>
            <button
              onClick={() => handleSelectDate(tomorrow)}
              className={cn(
                'px-4 py-2 rounded-lg border text-sm font-medium transition-all',
                selectedDate === tomorrow
                  ? 'bg-emerald-700 text-white border-emerald-700'
                  : 'border-gray-300 text-gray-700 hover:border-emerald-500',
              )}
            >
              Tomorrow
            </button>
            <button
              onClick={() => setShowDatePicker(v => !v)}
              className={cn(
                'flex items-center gap-2 px-4 py-2 rounded-lg border text-sm font-medium transition-all',
                showDatePicker
                  ? 'bg-emerald-700 text-white border-emerald-700'
                  : 'border-gray-300 text-gray-700 hover:border-emerald-500',
              )}
            >
              <Calendar className="w-4 h-4" /> Choose Date
            </button>
          </div>

          {showDatePicker && (
            <div className="mt-3">
              <MiniCalendar
                slotsByDate={slotsByDate}
                selectedDate={selectedDate}
                onSelectDate={handleSelectDate}
              />
            </div>
          )}
        </div>

        {/* Pilihan Tiket */}
        <div>
          <h2 className="font-bold text-gray-800 mb-3 flex items-center gap-2">
            <span className="text-emerald-600">&#10148;</span> Ticket Options
          </h2>

          <div className="space-y-4">
            {product.variants.filter(v => v.isActive).map(variant => {
              const isSelected = selectedVariantId === variant.id
              const lowestPrice = variant.priceAdult

              return (
                <div
                  key={variant.id}
                  className="border border-gray-200 rounded-xl overflow-hidden bg-white"
                >
                  {/* Variant header */}
                  <div className="flex items-center justify-between p-4">
                    <div>
                      <p className="font-semibold text-gray-800">{variant.label}</p>
                      <p className="text-sm text-gray-500 mt-0.5">
                        From <span className="font-semibold text-gray-800">{formatRupiah(lowestPrice)}</span>
                      </p>
                    </div>
                    <button
                      onClick={() => handleSelectVariant(variant.id)}
                      className={cn(
                        'px-5 py-2 rounded-lg text-sm font-semibold transition-all',
                        isSelected
                          ? 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                          : 'bg-emerald-700 text-white hover:bg-emerald-800',
                      )}
                    >
                      {isSelected ? 'Cancel' : 'Select'}
                    </button>
                  </div>

                  {/* Expanded variant detail + date/qty picker */}
                  {isSelected && (
                    <div className="border-t border-gray-100 p-4 space-y-5 bg-gray-50/50">
                      {/* Slot picker (if multiple slots) */}
                      {loadingAvail && (
                        <p className="text-sm text-gray-400">Loading schedule...</p>
                      )}
                      {!loadingAvail && (
                        <>
                          {/* Date shown */}
                          <div className="flex items-center gap-2 text-sm text-gray-600">
                            <Calendar className="w-4 h-4 text-emerald-600" />
                            <span>
                              Visit date:{' '}
                              <span className="font-semibold text-gray-800">
                                {new Date(selectedDate + 'T00:00:00').toLocaleDateString('en-GB', {
                                  weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
                                })}
                              </span>
                            </span>
                          </div>

                          {/* Calendar picker inline */}
                          <MiniCalendar
                            slotsByDate={slotsByDate}
                            selectedDate={selectedDate}
                            onSelectDate={(d) => { setSelectedDate(d); setSelectedSlotId(null) }}
                          />

                          {/* Time slot selection */}
                          {slotsForDate.length > 1 && (
                            <div>
                              <p className="text-sm font-semibold text-gray-700 mb-2">Select Time</p>
                              <div className="flex flex-wrap gap-2">
                                {slotsForDate.map(slot => (
                                  <button
                                    key={slot.id}
                                    disabled={slot.status === 'full' || slot.status === 'blocked'}
                                    onClick={() => setSelectedSlotId(slot.id)}
                                    className={cn(
                                      'px-3 py-1.5 rounded-lg border text-sm transition-all',
                                      selectedSlotId === slot.id
                                        ? 'border-emerald-600 bg-emerald-50 text-emerald-700'
                                        : slot.status === 'full' || slot.status === 'blocked'
                                        ? 'border-gray-200 text-gray-300 cursor-not-allowed'
                                        : 'border-gray-200 hover:border-emerald-400',
                                    )}
                                  >
                                    {slot.timeSlot ?? 'All Day'}
                                    <span className={cn('ml-1 text-xs', {
                                      'text-emerald-500': slot.status === 'available',
                                      'text-amber-500': slot.status === 'limited',
                                      'text-red-400': slot.status === 'full',
                                    })}>
                                      ({slot.remaining})
                                    </span>
                                  </button>
                                ))}
                              </div>
                            </div>
                          )}

                          {slotsForDate.length === 0 && (
                            <div className="flex items-center gap-2 text-sm text-amber-600 bg-amber-50 rounded-lg p-3">
                              <AlertCircle className="w-4 h-4 flex-shrink-0" />
                              No schedule available for this date.
                            </div>
                          )}
                        </>
                      )}

                      {/* Quantity */}
                      <div>
                        <p className="text-sm font-semibold text-gray-700 mb-3">Quantity</p>
                        <div className="space-y-3">
                          {/* Adult */}
                          <div className="flex items-center justify-between">
                            <div>
                              <p className="font-medium text-gray-800 text-sm">Adult</p>
                              <p className="text-xs text-gray-400">
                                Age range ({variant.adultMinAge} - {variant.adultMaxAge})
                              </p>
                            </div>
                            <div className="flex items-center gap-3">
                              <span className="text-sm font-semibold text-gray-700">
                                {formatRupiah(variant.priceAdult)}
                              </span>
                              <div className="flex items-center gap-2 border border-gray-200 rounded-lg overflow-hidden">
                                <button
                                  onClick={() => setQtyAdult(q => Math.max(0, q - 1))}
                                  className="w-8 h-8 flex items-center justify-center bg-gray-100 hover:bg-gray-200 transition-colors text-gray-600"
                                >
                                  <Minus className="w-3.5 h-3.5" />
                                </button>
                                <span className="w-8 text-center text-sm font-semibold">{qtyAdult}</span>
                                <button
                                  onClick={() => setQtyAdult(q => Math.min(variant.maxQty, q + 1))}
                                  className="w-8 h-8 flex items-center justify-center bg-emerald-600 hover:bg-emerald-700 transition-colors text-white"
                                >
                                  <Plus className="w-3.5 h-3.5" />
                                </button>
                              </div>
                            </div>
                          </div>

                          {/* Child */}
                          {variant.priceChild > 0 && (
                            <div className="flex items-center justify-between">
                              <div>
                                <p className="font-medium text-gray-800 text-sm">Child</p>
                                <p className="text-xs text-gray-400">
                                  Age range ({variant.childMinAge} - {variant.childMaxAge})
                                </p>
                              </div>
                              <div className="flex items-center gap-3">
                                <span className="text-sm font-semibold text-gray-700">
                                  {formatRupiah(variant.priceChild)}
                                </span>
                                <div className="flex items-center gap-2 border border-gray-200 rounded-lg overflow-hidden">
                                  <button
                                    onClick={() => setQtyChild(q => Math.max(0, q - 1))}
                                    className="w-8 h-8 flex items-center justify-center bg-gray-100 hover:bg-gray-200 transition-colors text-gray-600"
                                  >
                                    <Minus className="w-3.5 h-3.5" />
                                  </button>
                                  <span className="w-8 text-center text-sm font-semibold">{qtyChild}</span>
                                  <button
                                    onClick={() => setQtyChild(q => Math.min(variant.maxQty, q + 1))}
                                    className="w-8 h-8 flex items-center justify-center bg-emerald-600 hover:bg-emerald-700 transition-colors text-white"
                                  >
                                    <Plus className="w-3.5 h-3.5" />
                                  </button>
                                </div>
                              </div>
                            </div>
                          )}
                        </div>
                      </div>

                      {/* Add to cart */}
                      <div className="flex items-center justify-between pt-2 border-t border-gray-100">
                        {totalPrice > 0 && (
                          <div className="text-sm text-gray-500">
                            Total: <span className="font-bold text-gray-900 text-base">{formatRupiah(totalPrice)}</span>
                          </div>
                        )}
                        <button
                          onClick={handleAddToCart}
                          disabled={!canAddToCart}
                          className={cn(
                            'ml-auto px-6 py-2.5 rounded-lg font-semibold text-sm transition-all',
                            canAddToCart
                              ? 'bg-emerald-700 text-white hover:bg-emerald-800'
                              : 'bg-gray-200 text-gray-400 cursor-not-allowed',
                          )}
                        >
                          Add to cart
                        </button>
                      </div>
                    </div>
                  )}

                  {/* Collapsed detail expand */}
                  {!isSelected && (
                    <div className="px-4 pb-3">
                      <VariantDetailExpand variant={variant} product={product} />
                    </div>
                  )}
                </div>
              )
            })}

            {product.variants.filter(v => v.isActive).length === 0 && (
              <div className="text-center py-10 text-gray-400 text-sm">
                No tickets available at this time.
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}
