# Audio de prueba

Tonos sintéticos de 1 s a 440 Hz (16 kHz) generados con FFmpeg para las pruebas de subida y reproducción. No contienen
voz ni material de terceros; forman parte del repositorio bajo su licencia.

```bash
S="sine=frequency=440:duration=1:sample_rate=16000"
ffmpeg -f lavfi -i "$S" -ac 1 -c:a libmp3lame -b:a 32k tone.mp3
ffmpeg -f lavfi -i "$S" -ac 1 -c:a aac -b:a 32k tone.m4a
ffmpeg -f lavfi -i "$S" -ac 1 -c:a libopus -b:a 16k tone.opus
ffmpeg -f lavfi -i "$S" -ac 2 -c:a vorbis -strict -2 tone.ogg   # el codificador Vorbis nativo exige 2 canales
ffmpeg -f lavfi -i "$S" -ac 1 -c:a pcm_s16le tone.wav
ffmpeg -f lavfi -i "$S" -ac 1 -c:a libopus -b:a 16k tone.webm
ffmpeg -f lavfi -i "$S" -ac 1 -c:a aac -b:a 16k tone.3gp
```

`tone.3gp` es un formato que LexiCán no admite (los navegadores no reproducen 3GP/AMR de forma fiable).
