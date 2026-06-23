"use client";

import { create } from "zustand";

interface Staff {
  id: number;
  name: string;
  email: string;
  role: "staff" | "instructor";
}

interface AuthState {
  token: string | null;
  staff: Staff | null;
  setAuth: (token: string, staff: Staff) => void;
  logout: () => void;
}

export const useAuthStore = create<AuthState>((set) => ({
  token: typeof window !== "undefined" ? localStorage.getItem("scanner_token") : null,
  staff: null,
  setAuth: (token, staff) => {
    if (typeof window !== "undefined") localStorage.setItem("scanner_token", token);
    set({ token, staff });
  },
  logout: () => {
    if (typeof window !== "undefined") localStorage.removeItem("scanner_token");
    set({ token: null, staff: null });
  },
}));
