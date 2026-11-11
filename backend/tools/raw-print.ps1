# Send a file's bytes to a Windows printer as RAW data — no driver rendering, no print dialog.
# Used for USB receipt printers (api/usb_printer.php): the server builds the same ESC/POS bytes
# it sends to a Network LAN printer, so a USB bill comes out dot-for-dot identical to a LAN one.
#
#   powershell -NoProfile -ExecutionPolicy Bypass -File raw-print.ps1 -Printer "XP-80C" -File job.bin
#
# Exit code 0 = sent; anything else = failed (the message is on stdout).
param(
    [Parameter(Mandatory = $true)][string]$Printer,
    [Parameter(Mandatory = $true)][string]$File
)
$ErrorActionPreference = 'Stop'

# The winspool wrapper is compiled once and cached next to the temp files; later prints only load it.
$dll = Join-Path $env:TEMP 'MiniPosRawPrint_v1.dll'
if (-not ('MiniPosRawPrint' -as [type])) {
    if (-not (Test-Path $dll)) {
        Add-Type -OutputAssembly $dll -TypeDefinition @'
using System;
using System.Runtime.InteropServices;
public static class MiniPosRawPrint {
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
    public static string Send(string printer, byte[] data) {
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
}
'@
    }
    Add-Type -Path $dll
}

try {
    $err = [MiniPosRawPrint]::Send($Printer, [System.IO.File]::ReadAllBytes($File))
} catch {
    $err = $_.Exception.Message
}
if ($err) { Write-Output $err; exit 1 }
exit 0
