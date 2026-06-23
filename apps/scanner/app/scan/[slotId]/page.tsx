"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { useRouter, useParams } from "next/navigation";
import jsQR from "jsqr";
import { api } from "@/lib/api";
import { useAuthStore } from "@/store/auth";

type ScanResult =
  | { status: "valid"; guest_name: string; activity_name: string; pax_count: number; slot_time: string }
  | { status: "already_scanned"; checked_in_at: string }
  | { status: "invalid" };

export default function ScanPage() {
  const router = useRouter();
  const params = useParams<{ slotId: string }>();
  const token = useAuthStore((s) => s.token);

  const videoRef = useRef<HTMLVideoElement>(null);
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const rafRef = useRef<number | null>(null);
  const [scanResult, setScanResult] = useState<ScanResult | null>(null);
  const [processing, setProcessing] = useState(false);
  const [cameraError, setCameraError] = useState("");

  useEffect(() => {
    if (!token) { router.replace("/login"); return; }
    startCamera();
    return () => stopCamera();
  }, [token]);

  async function startCamera() {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } });
      if (videoRef.current) {
        videoRef.current.srcObject = stream;
        videoRef.current.play();
        rafRef.current = requestAnimationFrame(tick);
      }
    } catch {
      setCameraError("Kamera tidak dapat diakses. Pastikan izin kamera sudah diberikan.");
    }
  }

  function stopCamera() {
    if (rafRef.current) cancelAnimationFrame(rafRef.current);
    const stream = videoRef.current?.srcObject as MediaStream | null;
    stream?.getTracks().forEach((t) => t.stop());
  }

  const tick = useCallback(() => {
    const video = videoRef.current;
    const canvas = canvasRef.current;
    if (!video || !canvas || processing || scanResult) return;

    if (video.readyState === video.HAVE_ENOUGH_DATA) {
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      const ctx = canvas.getContext("2d");
      if (ctx) {
        ctx.drawImage(video, 0, 0);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height);
        if (code) {
          handleQrDetected(code.data);
          return;
        }
      }
    }
    rafRef.current = requestAnimationFrame(tick);
  }, [processing, scanResult]);

  useEffect(() => {
    if (!processing && !scanResult) {
      rafRef.current = requestAnimationFrame(tick);
    }
    return () => { if (rafRef.current) cancelAnimationFrame(rafRef.current); };
  }, [tick, processing, scanResult]);

  async function handleQrDetected(qrToken: string) {
    if (processing) return;
    setProcessing(true);
    try {
      const { data } = await api.post("/staff/bookings/validate", {
        qr_token: qrToken,
        slot_id: params.slotId,
      });
      setScanResult(data);
    } catch {
      setScanResult({ status: "invalid" });
    } finally {
      setProcessing(false);
    }
  }

  function resetScan() {
    setScanResult(null);
    rafRef.current = requestAnimationFrame(tick);
  }

  const bgColor =
    scanResult?.status === "valid" ? "bg-green-600"
    : scanResult?.status === "already_scanned" ? "bg-yellow-500"
    : scanResult?.status === "invalid" ? "bg-red-600"
    : "";

  return (
    <main className="relative min-h-screen overflow-hidden">
      <div className="absolute inset-0 bg-black">
        <video ref={videoRef} className="h-full w-full object-cover" playsInline muted />
        <canvas ref={canvasRef} className="hidden" />
        {/* Viewfinder overlay */}
        {!scanResult && !cameraError && (
          <div className="absolute inset-0 flex items-center justify-center">
            <div className="h-56 w-56 rounded-2xl border-4 border-white/70" />
          </div>
        )}
      </div>

      {/* Top bar */}
      <div className="absolute top-0 left-0 right-0 flex items-center justify-between px-5 py-4 bg-black/40">
        <button onClick={() => router.back()} className="text-white text-sm font-medium">← Kembali</button>
        <p className="text-white text-sm">Arahkan ke QR Code tamu</p>
      </div>

      {cameraError && (
        <div className="absolute inset-0 flex items-center justify-center bg-black/80 px-6">
          <p className="text-center text-red-400">{cameraError}</p>
        </div>
      )}

      {/* Result overlay */}
      {scanResult && (
        <div className={`absolute inset-0 flex flex-col items-center justify-center px-8 ${bgColor}`}>
          {scanResult.status === "valid" && (
            <>
              <p className="text-5xl mb-4">✓</p>
              <p className="text-2xl font-bold text-center">VALID</p>
              <p className="mt-4 text-xl font-semibold text-center">{scanResult.guest_name}</p>
              <p className="text-base text-white/80 mt-1">{scanResult.activity_name}</p>
              <p className="text-base text-white/80">{scanResult.slot_time} · {scanResult.pax_count} pax</p>
            </>
          )}
          {scanResult.status === "already_scanned" && (
            <>
              <p className="text-5xl mb-4">⚠</p>
              <p className="text-2xl font-bold">SUDAH SCAN</p>
              <p className="mt-3 text-base text-white/80">Check-in pada {scanResult.checked_in_at}</p>
            </>
          )}
          {scanResult.status === "invalid" && (
            <>
              <p className="text-5xl mb-4">✗</p>
              <p className="text-2xl font-bold">TIDAK VALID</p>
              <p className="mt-3 text-base text-white/80 text-center">QR tidak dikenali atau bukan untuk slot ini</p>
            </>
          )}
          <button
            onClick={resetScan}
            className="mt-8 rounded-xl bg-white/20 px-8 py-3 text-base font-semibold hover:bg-white/30 transition"
          >
            Scan Berikutnya
          </button>
        </div>
      )}

      {processing && (
        <div className="absolute inset-0 flex items-center justify-center bg-black/60">
          <div className="h-10 w-10 animate-spin rounded-full border-4 border-white border-t-transparent" />
        </div>
      )}
    </main>
  );
}
