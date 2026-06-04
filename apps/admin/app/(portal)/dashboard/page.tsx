import { LayoutDashboard } from 'lucide-react'

export default function DashboardPage() {
  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <LayoutDashboard size={24} className="text-muted-foreground" />
        <div>
          <h1 className="text-2xl font-bold text-foreground">Dasbor</h1>
          <p className="text-sm text-muted-foreground">Selamat datang di panel admin Amartha eTicket</p>
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {[
          { label: 'Total Pesanan', value: '—', color: 'text-blue-600' },
          { label: 'Pesanan Hari Ini', value: '—', color: 'text-emerald-600' },
          { label: 'Tiket Terpakai', value: '—', color: 'text-amber-600' },
          { label: 'Pendapatan', value: '—', color: 'text-violet-600' },
        ].map(stat => (
          <div key={stat.label} className="rounded-xl border border-border bg-card p-5">
            <p className="text-sm text-muted-foreground">{stat.label}</p>
            <p className={`mt-1 text-2xl font-bold ${stat.color}`}>{stat.value}</p>
          </div>
        ))}
      </div>

      <div className="rounded-xl border border-border bg-card p-5">
        <p className="text-sm text-muted-foreground text-center py-8">
          Data dasbor akan ditampilkan di sini.
        </p>
      </div>
    </div>
  )
}
