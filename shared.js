/* ═══════════════════════════════════════════
   shared.js — PMB ITB 2025
   Utility bersama: api(), toast(), statusBadge(), fmtDate(), logout()
   ═══════════════════════════════════════════ */

const API = "api/";

/** Panggil backend PHP */
async function api(ep, method = "GET", body = null) {
  const o = {
    method,
    headers: { "Content-Type": "application/json" },
    credentials: "include",
  };
  if (body) o.body = JSON.stringify(body);
  return (await fetch(API + ep, o)).json();
}

/** Tampilkan notifikasi toast */
function toast(msg, type = "success") {
  const t = document.createElement("div");
  t.className = `toast toast-${type}`;
  t.innerHTML = (type === "success" ? "✓" : "✕") + " " + msg;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}

/** Render badge status */
function statusBadge(s) {
  const m = {
    draft: "badge-draft",
    pending: "badge-pending",
    verified: "badge-verified",
    accepted: "badge-accepted",
    rejected: "badge-rejected",
  };
  const l = {
    draft: "Draft",
    pending: "Menunggu",
    verified: "Terverifikasi",
    accepted: "Diterima",
    rejected: "Ditolak",
  };
  return `<span class="badge ${m[s] || "badge-draft"}">${l[s] || s}</span>`;
}

/** Format tanggal ke lokal Indonesia */
function fmtDate(d) {
  return d
    ? new Date(d).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
      })
    : "—";
}

/** Logout dan redirect ke halaman login */
async function logout() {
  await api("auth.php?action=logout", "POST");
  window.location.href = "index.html";
}

/**
 * Toggle visibilitas password pada input field.
 *
 * Cara pakai — tambahkan wrapper + tombol di sebelah input password:
 *
 *   <div class="pw-wrap">
 *     <input type="password" id="f-password" placeholder="Password" />
 *     <button type="button" class="pw-eye" onclick="togglePassword('f-password', this)" aria-label="Tampilkan password">
 *       👁
 *     </button>
 *   </div>
 *
 * Tambahkan CSS berikut ke shared.css atau <style> di halaman login:
 *
 *   .pw-wrap {
 *     position: relative;
 *     display: flex;
 *     align-items: center;
 *   }
 *   .pw-wrap input {
 *     width: 100%;
 *     padding-right: 44px;   <- beri ruang untuk tombol
 *   }
 *   .pw-eye {
 *     position: absolute;
 *     right: 12px;
 *     background: none;
 *     border: none;
 *     cursor: pointer;
 *     font-size: 16px;
 *     color: var(--muted);
 *     padding: 0;
 *     line-height: 1;
 *     transition: opacity 0.2s;
 *   }
 *   .pw-eye:hover { opacity: 0.75; }
 */
function togglePassword(inputId, btn) {
  const input = document.getElementById(inputId);
  if (!input) return;

  const isHidden = input.type === "password";
  input.type = isHidden ? "text" : "password";

  /* Ganti ikon: 👁 = tampil, 🙈 = sembunyikan */
  btn.textContent = isHidden ? "🙈" : "👁";
  btn.setAttribute(
    "aria-label",
    isHidden ? "Sembunyikan password" : "Tampilkan password",
  );
}
