"use client";

import { useEffect, useState } from "react";
import { useRouter, useParams } from "next/navigation";
import { api } from "@/lib/api";
import { useAuthStore } from "@/store/auth";

interface CheckIn {
  booking_code: string;
  guest_name: string;
  pax_count: number;
  checked_in_at: string;
}

export default function HistoryPage() {
  const router = useRouter();
  const params = useParams<{ slotId: string }>();
  const token = useAuthStore((s) => s.token);
  const [list, setList] = useState<CheckIn[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!token) { router.replace("/login"); return; }
    api.get(`/staff/slots/${params.slotId}/checkins`)
      .then((r) => setList(r.data.data ?? []))
      .finally(() => setLoading(false));
  }, [token, params.slotId, router]);

  return (
    <main className="max-w-lg mx-auto px-4 py-6 space-y-4">
      <div className="flex items-center gap-3">
        <button onClick={() => router.back()} className="text-gray-400 hover:text-white">←</button>
        <h1 className="text-xl font-bold">Riwayat Check-in</h1>
      </div>
      {loading ? (
        <div className="flex justify-center py-12">
          <div className="h-8 w-8 animate-spin rounded-full border-4 border-blue-500 border-t-transparent" />
        </div>
      ) : list.length === 0 ? (
        <p className="text-center text-gray-400 py-12">Belum ada tamu yang check-in</p>
      ) : (
        <div className="space-y-2">
          {list.map((item) => (
            <div key={item.booking_code} className="rounded-xl bg-gray-800 px-5 py-4 flex justify-between items-center">
              <div>
                <p className="font-semibold">{item.guest_name}</p>
                <p className="text-sm text-gray-400">{item.booking_code} · {item.pax_count} pax</p>
              </div>
              <p className="text-sm text-green-400">{item.checked_in_at}</p>
            </div>
          ))}
        </div>
      )}
    </main>
  );
}
