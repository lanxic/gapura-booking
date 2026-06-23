import { z } from "zod";

// ─── Booking flow schemas ─────────────────────────────────────────────────────

export const selectSlotSchema = z.object({
  slot_id: z.number().int().positive(),
  pax_count: z.number().int().min(1).max(50),
});

export const guestInfoSchema = z.object({
  guest_name: z.string().min(2, "Nama minimal 2 karakter"),
  guest_email: z.string().email("Format email tidak valid"),
  guest_phone: z.string().min(8, "Nomor HP tidak valid"),
  notes: z.string().optional(),
  participants: z
    .array(z.object({ name: z.string().min(1) }))
    .optional(),
});

export const checkoutSchema = z.object({
  slot_id: z.number().int().positive(),
  pax_count: z.number().int().min(1),
  guest_name: z.string().min(2),
  guest_email: z.string().email(),
  guest_phone: z.string().min(8),
  notes: z.string().optional(),
  addons: z
    .array(z.object({ addon_id: z.number().int(), quantity: z.number().int().min(1) }))
    .optional(),
  promo_code: z.string().optional(),
  payment_plan: z.enum(["FULL", "DP30", "DP50", "DP70"]).default("FULL"),
});

// ─── Auth schemas ─────────────────────────────────────────────────────────────

export const loginSchema = z.object({
  email: z.string().email("Format email tidak valid"),
  password: z.string().min(6, "Password minimal 6 karakter"),
});

export const registerSchema = loginSchema
  .extend({
    name: z.string().min(2, "Nama minimal 2 karakter"),
    phone: z.string().min(8, "Nomor HP tidak valid"),
    password_confirmation: z.string(),
  })
  .refine((d) => d.password === d.password_confirmation, {
    message: "Konfirmasi password tidak cocok",
    path: ["password_confirmation"],
  });

// ─── Promo code schema ────────────────────────────────────────────────────────

export const validatePromoSchema = z.object({
  code: z.string().min(1),
  amount: z.number().positive(),
  activity_id: z.number().int().positive().optional(),
});
