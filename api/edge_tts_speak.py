import sys
import asyncio
import base64
import edge_tts

async def main():
    if len(sys.argv) < 3:
        sys.stderr.write("Usage: python edge_tts_speak.py <base64_text> <output_file> [voice]\n")
        sys.exit(1)

    try:
        raw_b64 = sys.argv[1]
        text = base64.b64decode(raw_b64).decode('utf-8')
        output_file = sys.argv[2]
        voice = sys.argv[3] if len(sys.argv) > 3 and sys.argv[3] else "lo-LA-KeomanyNeural"

        # ຖ້າຊື່ສຽງບໍ່ແມ່ນ lo-LA- ຂອງ Edge (ເຊັ່ນ: ຕິດຊື່ sengchanh ຂອງ SiangMuan ມາ) ໃຫ້ປ່ຽນເປັນ KeomanyNeural
        if not voice.startswith("lo-LA-"):
            voice = "lo-LA-KeomanyNeural"

        communicate = edge_tts.Communicate(text, voice)
        await communicate.save(output_file)
        print("SUCCESS")
    except Exception as e:
        sys.stderr.write(f"EdgeTTS Error: {e}\n")
        sys.exit(1)

if __name__ == "__main__":
    asyncio.run(main())
