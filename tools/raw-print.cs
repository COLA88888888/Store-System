// ສົ່ງ byte ຂອງໄຟລ໌ໄປເຄື່ອງພິມ Windows ແບບ RAW — ໂຕດຽວກັບ tools/raw-print.ps1 ແຕ່ເປັນ .exe.
// api/usb_printer.php ຄອມໄພລ໌ໄຟລ໌ນີ້ດ້ວຍ csc.exe (ມີມາກັບ .NET Framework 4 ໃນ Windows) ຄັ້ງດຽວ
// ແລ້ວເກັບ .exe ໄວ້ໃນໂຟນເດີ temp: ເປີດ .exe ໃຊ້ ~0.05 s ແທນ PowerShell ~2 s ຕໍ່ບິນ.
//
//   raw-print.exe "XP-80C" job.bin
//
// Exit code 0 = ສົ່ງແລ້ວ; ອື່ນໆ = ບໍ່ສຳເລັດ (ຂໍ້ຄວາມຢູ່ stdout).
using System;
using System.IO;
using System.Runtime.InteropServices;

public static class MiniPosRawPrintExe {
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Unicode)]
    public class DOCINFO { public string pDocName; public string pOutputFile; public string pDataType; }
    [DllImport("winspool.drv", CharSet = CharSet.Unicode, SetLastError = true)]
    static extern bool OpenPrinter(string name, out IntPtr h, IntPtr pd);
    [DllImport("winspool.drv", SetLastError = true)] static extern bool ClosePrinter(IntPtr h);
    [DllImport("winspool.drv", CharSet = CharSet.Unicode, SetLastError = true)]
    static extern int StartDocPrinter(IntPtr h, int level, [In] DOCINFO di);
    [DllImport("winspool.drv", SetLastError = true)] static extern bool EndDocPrinter(IntPtr h);
    [DllImport("winspool.drv", SetLastError = true)] static extern bool StartPagePrinter(IntPtr h);
    [DllImport("winspool.drv", SetLastError = true)] static extern bool EndPagePrinter(IntPtr h);
    [DllImport("winspool.drv", SetLastError = true)]
    static extern bool WritePrinter(IntPtr h, IntPtr buf, int len, out int written);

    static string Send(string printer, byte[] data) {
        IntPtr h;
        if (!OpenPrinter(printer, out h, IntPtr.Zero)) return "OpenPrinter failed (" + Marshal.GetLastWin32Error() + ")";
        try {
            DOCINFO di = new DOCINFO(); di.pDocName = "MiniPos receipt"; di.pDataType = "RAW";
            if (StartDocPrinter(h, 1, di) == 0) return "StartDocPrinter failed (" + Marshal.GetLastWin32Error() + ")";
            try {
                StartPagePrinter(h);
                IntPtr p = Marshal.AllocCoTaskMem(data.Length);
                try {
                    Marshal.Copy(data, 0, p, data.Length);
                    int written;
                    if (!WritePrinter(h, p, data.Length, out written)) return "WritePrinter failed (" + Marshal.GetLastWin32Error() + ")";
                    if (written != data.Length) return "WritePrinter wrote " + written + " of " + data.Length + " bytes";
                } finally { Marshal.FreeCoTaskMem(p); }
                EndPagePrinter(h);
            } finally { EndDocPrinter(h); }
        } finally { ClosePrinter(h); }
        return "";
    }

    public static int Main(string[] args) {
        if (args.Length != 2) { Console.WriteLine("usage: raw-print.exe <printer> <file>"); return 2; }
        string err;
        try { err = Send(args[0], File.ReadAllBytes(args[1])); }
        catch (Exception e) { err = e.Message; }
        if (err.Length > 0) { Console.WriteLine(err); return 1; }
        return 0;
    }
}
