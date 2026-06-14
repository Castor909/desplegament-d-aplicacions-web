import io
from pypdf import PdfReader, PdfWriter
from reportlab.pdfgen import canvas

ORIG="DAW_P12_Andreev.pdf"; TAIL="build/tail.pdf"; OUT="build/final.pdf"
NAME="Stepan Andreev - Practica 12 (Gitea)"

w=PdfWriter()
orig=PdfReader(ORIG)
for i in range(20):            # paginas 1-20 (secciones 1-6)
    w.add_page(orig.pages[i])
for p in PdfReader(TAIL).pages: # cola: tabla + bonus corregido
    w.add_page(p)

buf=io.BytesIO()
c=canvas.Canvas(buf, pagesize=(612,792))
c.setFont("Helvetica",8); c.setFillGray(0.45)
c.drawString(40,22,NAME)
c.save(); buf.seek(0)
stamp=PdfReader(buf).pages[0]
for pg in w.pages:
    pg.merge_page(stamp)

with open(OUT,"wb") as f: w.write(f)
print("OK paginas:", len(w.pages))
