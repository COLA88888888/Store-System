// ເປີດຈໍລູກຄ້າເຕັມຈໍ (ຄືກົດ F11) ຢູ່ຈໍທີ 2 ໂດຍອັດຕະໂນມັດ — ສຳລັບຄອມ POS 2 ໜ້າຈໍ ທີ່ຈໍລູກຄ້າແຕະບໍ່ໄດ້.
// pages/pos/customer_display.php (?action=native_f11) ຄອມໄພລ໌ໄຟລ໌ນີ້ດ້ວຍ csc.exe ຄັ້ງດຽວ ແລ້ວເອີ້ນໃຊ້:
//
//   cd-fullscreen.exe "WlaoDev Customer Display" [--force]
//
// 1. ຊອກໜ້າຕ່າງທີ່ຫົວຂໍ້ຂຶ້ນຕົ້ນດ້ວຍຂໍ້ຄວາມນັ້ນ
// 2. ຍ້າຍໄປຈໍທີ່ບໍ່ແມ່ນຈໍຂອງໜ້າຕ່າງທີ່ໃຊ້ຢູ່ (ຈໍພະນັກງານ) — ບໍ່ຕ້ອງຂໍສິດ Window Management ຈາກ Chrome
// 3. ດຶງຂຶ້ນໜ້າ (Windows ບໍ່ໃຫ້ໂປຣແກຣມເບື້ອງຫຼັງດຶງງ່າຍໆ → AttachThreadInput, ບໍ່ພໍ → ກົດ ALT ກ່ອນ)
// 4. ກົດ F11 ສະເພາະເມື່ອດຶງຂຶ້ນໜ້າໄດ້ແທ້ (ບໍ່ດັ່ງນັ້ນ F11 ຈະໄປໂດນຈໍພະນັກງານ)
// 5. ຄືນ focus ໃຫ້ໜ້າຕ່າງເດີມ (ພະນັກງານພິມ/ສະແກນຕໍ່ໄດ້ທັນທີ)
// ເຕັມຈໍຢູ່ແລ້ວ → ບໍ່ກົດຊ້ຳ (F11 ສະຫຼັບ ເປີດ/ປິດ) ເວັ້ນແຕ່ --force. Exit 0 = OK/ALREADY, ອື່ນໆ = ບໍ່ສຳເລັດ (ຂໍ້ຄວາມ stdout).
using System;
using System.Collections.Generic;
using System.Runtime.InteropServices;
using System.Text;
using System.Threading;

public static class CdFullscreen {
    delegate bool EnumWinProc(IntPtr h, IntPtr l);
    delegate bool MonEnumProc(IntPtr m, IntPtr dc, ref RECT r, IntPtr d);
    [StructLayout(LayoutKind.Sequential)] struct RECT { public int L, T, R, B; }
    [StructLayout(LayoutKind.Sequential)] struct MONITORINFO { public int cbSize; public RECT rcMonitor; public RECT rcWork; public uint dwFlags; }

    [DllImport("user32.dll")] static extern bool EnumWindows(EnumWinProc cb, IntPtr l);
    [DllImport("user32.dll", CharSet = CharSet.Unicode)] static extern int GetWindowText(IntPtr h, StringBuilder s, int n);
    [DllImport("user32.dll")] static extern bool IsWindowVisible(IntPtr h);
    [DllImport("user32.dll")] static extern IntPtr GetForegroundWindow();
    [DllImport("user32.dll")] static extern bool SetForegroundWindow(IntPtr h);
    [DllImport("user32.dll")] static extern bool BringWindowToTop(IntPtr h);
    [DllImport("user32.dll")] static extern bool ShowWindow(IntPtr h, int cmd);
    [DllImport("user32.dll")] static extern bool IsIconic(IntPtr h);
    [DllImport("user32.dll")] static extern bool IsZoomed(IntPtr h);
    [DllImport("user32.dll")] static extern bool SetWindowPos(IntPtr h, IntPtr after, int x, int y, int w, int hh, uint flags);
    [DllImport("user32.dll")] static extern bool GetWindowRect(IntPtr h, out RECT r);
    [DllImport("user32.dll")] static extern IntPtr MonitorFromWindow(IntPtr h, uint flags);
    [DllImport("user32.dll")] static extern bool GetMonitorInfo(IntPtr m, ref MONITORINFO mi);
    [DllImport("user32.dll")] static extern bool EnumDisplayMonitors(IntPtr dc, IntPtr clip, MonEnumProc cb, IntPtr d);
    [DllImport("user32.dll")] static extern void keybd_event(byte vk, byte scan, uint flags, UIntPtr extra);
    [DllImport("user32.dll")] static extern uint GetWindowThreadProcessId(IntPtr h, IntPtr pid);
    [DllImport("user32.dll")] static extern bool AttachThreadInput(uint a, uint b, bool attach);
    [DllImport("kernel32.dll")] static extern uint GetCurrentThreadId();

    const byte VK_MENU = 0x12, VK_F11 = 0x7A;
    const uint KEYUP = 0x2;

    static IntPtr FindByTitlePrefix(string prefix) {
        IntPtr found = IntPtr.Zero;
        EnumWindows(delegate (IntPtr h, IntPtr l) {
            if (!IsWindowVisible(h)) return true;
            var sb = new StringBuilder(512);
            GetWindowText(h, sb, sb.Capacity);
            if (sb.ToString().StartsWith(prefix, StringComparison.OrdinalIgnoreCase)) { found = h; return false; }
            return true;
        }, IntPtr.Zero);
        return found;
    }

    static RECT MonitorRect(IntPtr mon) {
        var mi = new MONITORINFO(); mi.cbSize = Marshal.SizeOf(typeof(MONITORINFO));
        GetMonitorInfo(mon, ref mi);
        return mi.rcMonitor;
    }

    static bool IsPrimary(IntPtr mon) {
        var mi = new MONITORINFO(); mi.cbSize = Marshal.SizeOf(typeof(MONITORINFO));
        GetMonitorInfo(mon, ref mi);
        return (mi.dwFlags & 1) != 0;   // MONITORINFOF_PRIMARY
    }

    static bool Focus(IntPtr h) {
        if (GetForegroundWindow() == h) return true;
        uint me = GetCurrentThreadId();
        uint fg = GetWindowThreadProcessId(GetForegroundWindow(), IntPtr.Zero);
        AttachThreadInput(me, fg, true);
        BringWindowToTop(h);
        SetForegroundWindow(h);
        AttachThreadInput(me, fg, false);
        Thread.Sleep(60);
        if (GetForegroundWindow() == h) return true;
        // Windows ຍັງບລັອກ: ກົດ ALT ສັ້ນໆ ໃຫ້ລະບົບຍອມປ່ຽນໜ້າຕ່າງທີ່ໃຊ້ຢູ່
        keybd_event(VK_MENU, 0, 0, UIntPtr.Zero);
        keybd_event(VK_MENU, 0, KEYUP, UIntPtr.Zero);
        SetForegroundWindow(h);
        Thread.Sleep(80);
        return GetForegroundWindow() == h;
    }

    public static int Main(string[] args) {
        if (args.Length < 1) { Console.WriteLine("usage: cd-fullscreen.exe <title prefix> [--force]"); return 2; }
        bool force = Array.IndexOf(args, "--force") >= 0;
        IntPtr win = FindByTitlePrefix(args[0]);
        if (win == IntPtr.Zero) { Console.WriteLine("NOT_FOUND"); return 3; }
        IntPtr prev = GetForegroundWindow();

        // ຈໍປາຍທາງ: ຈໍທີ່ບໍ່ແມ່ນຈໍຂອງໜ້າຕ່າງພະນັກງານ (ໜ້າຕ່າງທີ່ໃຊ້ຢູ່); ມີຈໍດຽວ → ຈໍທີ່ໜ້າຕ່າງຢູ່
        var mons = new List<IntPtr>();
        EnumDisplayMonitors(IntPtr.Zero, IntPtr.Zero, delegate (IntPtr m, IntPtr dc, ref RECT r, IntPtr d) { mons.Add(m); return true; }, IntPtr.Zero);
        // ໜ້າຕ່າງທີ່ໃຊ້ຢູ່ແມ່ນຈໍລູກຄ້າເອງ (ເອີ້ນຕອນເປີດໜ້າ) → ບໍ່ຮູ້ຈໍພະນັກງານ → ເລືອກຈໍທີ່ບໍ່ແມ່ນຈໍຫຼັກ (Windows primary)
        IntPtr staffMon = (prev != IntPtr.Zero && prev != win) ? MonitorFromWindow(prev, 2) : IntPtr.Zero;
        IntPtr target = MonitorFromWindow(win, 2);
        foreach (IntPtr m in mons) {
            if (staffMon != IntPtr.Zero ? m != staffMon : !IsPrimary(m)) { target = m; break; }
        }
        RECT mr = MonitorRect(target);

        RECT wr; GetWindowRect(win, out wr);
        bool onTarget = MonitorFromWindow(win, 2) == target;
        bool isFull = onTarget && wr.L <= mr.L && wr.T <= mr.T && wr.R >= mr.R && wr.B >= mr.B;
        if (isFull && !force) { Console.WriteLine("ALREADY"); return 0; }

        if (!onTarget || !isFull) {
            if (IsIconic(win) || IsZoomed(win)) { ShowWindow(win, 9); Thread.Sleep(60); }   // SW_RESTORE: ກ່ອນຍ້າຍຈໍ
            if (!onTarget) {
                SetWindowPos(win, IntPtr.Zero, mr.L + 40, mr.T + 40, (mr.R - mr.L) - 80, (mr.B - mr.T) - 80, 0x0004 | 0x0040);
                Thread.Sleep(120);
            }
        }

        if (!Focus(win)) { Console.WriteLine("FOCUS_DENIED"); return 4; }
        keybd_event(VK_F11, 0, 0, UIntPtr.Zero);
        keybd_event(VK_F11, 0, KEYUP, UIntPtr.Zero);
        Thread.Sleep(250);

        // ຄືນໜ້າຕ່າງພະນັກງານ ໃຫ້ພິມ/ສະແກນຕໍ່ໄດ້ທັນທີ
        if (prev != IntPtr.Zero && prev != win) Focus(prev);
        Console.WriteLine("OK");
        return 0;
    }
}
