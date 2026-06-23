"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { api } from "@/lib/api";
import { useAuthStore } from "@/store/auth";

interface Slot {
  id: number;
  activity_name: string;
  start_time: string;
  end_time: string;
  booked_count: number;
  capacity: number;
}

export default function HomePage() {
  const router = useRouter();
  const { token, logout } = useAuthStore();
  const [slots, setSlots] = useState<Slot[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!token) { router.replace("/login"); return; }
    api.get("/staff/slots?date=today")
      .then((r) => setSlots(r.data.data ?? []))
      .catch(() => logout())
      .finally(() => setLoading(false));
  }, [token, router, logout]);

  if (loading) return <Loading />;

  return (
    <main className="max-w-lg mx-auto px-4 py-6 space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-bold">Slot Hari Ini</h1>
        <button onClick={logout} className="text-sm text-gray-400 hover:text-white">Logout</button>
      </div>
      {slots.length === 0 && (
        <p className="text-center text-gray-400 py-12">Tidak ada slot hari ini</p>
      )}
      <div className="space-y-3">
        {slots.map((slot) => (
          <div
            key={slot.id}
            onClick={() => router.push(`/scan/${slot.id}`)}
            className="flex items-center justify-between rounded-xl bg-gray-800 px-5 py-4 cursor-pointer hover:bg-gray-700 active:scale-[0.98] transition"
          >
            <div>
              <p className="font-semibold">{slot.activity_name}</p>
              <p className="text-sm text-gray-400">{slot.start_time} – {slot.end_time}</p>
            </div>
            <div className="text-right">
              <p className="text-lg font-bold">{slot.booked_count}<span className="text-sm text-gray-400">/{slot.capacity}</span></p>
              <p className="text-xs text-gray-400">tamu</p>
            </div>
          </div>
        ))}
      </div>
    </main>
  );
}

function Loading() {
  return (
    <div className="flex min-h-screen items-center justify-center">
      <div className="h-8 w-8 animate-spin rounded-full border-4 border-blue-500 border-t-transparent" />
    </div>
  );
}
