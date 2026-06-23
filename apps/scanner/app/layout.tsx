import type { Metadata, Viewport } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "QR Scanner — Activity Booking",
  description: "Internal staff app untuk validasi kehadiran tamu",
  manifest: "/manifest.json",
  appleWebApp: { capable: true, statusBarStyle: "default" },
};

export const viewport: Viewport = {
  themeColor: "#1e3a5f",
  width: "device-width",
  initialScale: 1,
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="id">
      <body className="bg-gray-950 text-white min-h-screen">{children}</body>
    </html>
  );
}
