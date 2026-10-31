"""
ເຄື່ອງສ້າງສຽງ Edge TTS ທີ່ເປີດຄ້າງໄວ້ (StoreMini) — ຟັງສະເພາະ 127.0.0.1.

ເປັນຫຍັງ: api/edge_tts_speak.py ເປີດ Python ໃໝ່ທຸກຂໍ້ຄວາມ (~0.2 s) + import edge_tts (~1.7 s) + ເຊື່ອມຕໍ່
Microsoft ຄັ້ງທຳອິດ (~2.4 s) = ~5 s ຕໍ່ຂໍ້ຄວາມໃໝ່. ໂປຣແກຣມນີ້ໂຫຼດໄວ້ເທື່ອດຽວ ແລະ ອຸ່ນການເຊື່ອມຕໍ່ໄວ້ກ່ອນ
→ ຂໍ້ຄວາມໃໝ່ ~0.9 s.

ໂປຣໂຕຄໍ (api/tts_speak.php): ສົ່ງ JSON 1 ແຖວ {"text", "voice", "file": "<md5>.mp3"} → ຕອບ "OK" ຫຼື "ERR <ເຫດຜົນ>".
ໄຟລ໌ຂຽນໃສ່ໂຟນເດີ cache ທີ່ໃຫ້ມາຕອນເປີດເທົ່ານັ້ນ (ຊື່ໄຟລ໌ຕ້ອງເປັນ md5.mp3).
ເປີດ: pythonw tts_daemon.py <cache_dir> [port]   — tts_speak.php ເປີດໃຫ້ເອງເມື່ອຍັງບໍ່ມີ.
ເປີດຊ້ຳ → bind port ບໍ່ໄດ້ → ອອກເອງ (ມີແຕ່ໂຕດຽວ).
"""
import asyncio
import json
import os
import re
import sys

import edge_tts

DEFAULT_VOICE = "lo-LA-KeomanyNeural"
NAME_RE = re.compile(r"^[0-9a-f]{32}\.mp3$")


async def synth(text, voice, path):
    tmp = path + ".part"
    await asyncio.wait_for(edge_tts.Communicate(text, voice).save(tmp), timeout=20)
    if not os.path.exists(tmp) or os.path.getsize(tmp) == 0:
        raise RuntimeError("empty audio")
    os.replace(tmp, path)


def make_handler(cache_dir):
    async def handle(reader, writer):
        reply = "ERR unknown"
        try:
            line = await asyncio.wait_for(reader.readline(), timeout=5)
            req = json.loads(line.decode("utf-8"))
            name = os.path.basename(str(req.get("file", "")))
            text = str(req.get("text", "")).strip()
            voice = str(req.get("voice") or DEFAULT_VOICE)
            if not voice.startswith("lo-LA-"):
                voice = DEFAULT_VOICE
            if not NAME_RE.match(name) or not text:
                raise ValueError("bad request")
            path = os.path.join(cache_dir, name)
            if not (os.path.exists(path) and os.path.getsize(path) > 0):
                await synth(text, voice, path)
            reply = "OK"
        except Exception as e:  # noqa: BLE001 — every failure goes back to PHP, which falls back
            reply = "ERR " + str(e).replace("\n", " ")[:200]
        try:
            writer.write((reply + "\n").encode("utf-8"))
            await writer.drain()
        finally:
            writer.close()
    return handle


async def warm_up(cache_dir):
    # ເຊື່ອມຕໍ່ Microsoft ໄວ້ລ່ວງໜ້າ — ຂໍ້ຄວາມທຳອິດຂອງລູກຄ້າບໍ່ຕ້ອງລໍ 2-3 ວິນາທີ
    path = os.path.join(cache_dir, "_warmup.mp3")
    try:
        await synth("ສະບາຍດີ", DEFAULT_VOICE, path)
    except Exception:
        pass
    finally:
        try:
            os.remove(path)
        except OSError:
            pass


async def main():
    cache_dir = os.path.abspath(sys.argv[1]) if len(sys.argv) > 1 else os.path.join(os.path.dirname(__file__), "..", "assets", "audio_cache")
    port = int(sys.argv[2]) if len(sys.argv) > 2 else 5599
    os.makedirs(cache_dir, exist_ok=True)
    try:
        server = await asyncio.start_server(make_handler(cache_dir), "127.0.0.1", port)
    except OSError:
        return  # ມີໂຕອື່ນເປີດຢູ່ແລ້ວ
    asyncio.create_task(warm_up(cache_dir))
    async with server:
        await server.serve_forever()


if __name__ == "__main__":
    asyncio.run(main())
