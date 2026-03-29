// Station Sign — A4 Landscape
// Generated via StationSignService, or compile manually:
//
// First generate QR codes:
//   qrencode -l H -t SVG -s 10 -o /tmp/qr-cash-full.svg      "https://bargeldabschoepfung24.de/s/TOKEN"
//   qrencode -l H -t SVG -s 10 -o /tmp/qr-change-request.svg "https://grossgeld-zu-kleingeld24.de/s/TOKEN"
//   qrencode -l H -t SVG -s 10 -o /tmp/qr-other.svg          "https://bargelddrama24.de/s/TOKEN"
//
// Then compile:
//   typst compile \
//     --root / \
//     --font-path resources/fonts/public-sans \
//     --input "station-name=Kasse 3" \
//     --input "station-location=Eingang Nord" \
//     --input "station-token=TOKEN" \
//     --input "logo-path=$PWD/public/logos/Horizontal - Light.svg" \
//     --input "photo-path=$PWD/storage/app/station-sign/photo.jpg" \
//     --input "qr-cash-full=/tmp/qr-cash-full.svg" \
//     --input "qr-change-request=/tmp/qr-change-request.svg" \
//     --input "qr-other=/tmp/qr-other.svg" \
//     --input "url-cash-full=https://bargeldabschoepfung24.de/s/TOKEN" \
//     --input "url-change-request=https://grossgeld-zu-kleingeld24.de/s/TOKEN" \
//     --input "url-other=https://bargelddrama24.de/s/TOKEN" \
//     resources/typst/station-sign.typ \
//     station-sign.pdf

#let station-name     = sys.inputs.at("station-name", default: "Kasse")
#let station-location = sys.inputs.at("station-location", default: "")
#let station-token    = sys.inputs.at("station-token", default: "")
#let photo-path       = sys.inputs.at("photo-path", default: "")
#let logo-path        = sys.inputs.at("logo-path", default: "")
#let qr-cash-full     = sys.inputs.at("qr-cash-full")
#let qr-change-req    = sys.inputs.at("qr-change-request")
#let qr-other         = sys.inputs.at("qr-other")
#let url-cash-full    = sys.inputs.at("url-cash-full")
#let url-change-req   = sys.inputs.at("url-change-request")
#let url-other        = sys.inputs.at("url-other")

#let gpn-white  = rgb("#f6f5f4")
#let gpn-orange = rgb("#ea5b0c")
#let gpn-black  = rgb("#121111")

#set page(paper: "a4", flipped: true, margin: (x: 18mm, y: 15mm), fill: gpn-white)
#set text(font: "Public Sans", lang: "de", size: 11pt, fill: gpn-black)

#grid(
  columns: (1fr, auto),
  align: (left + horizon, right + horizon),
  if logo-path != "" { image(logo-path, height: 10mm) },
  [
    #text(size: 11pt)[#station-name#if station-location != "" [ | #station-location]]
    #if station-token != "" [
      #linebreak()
      #text(size: 10pt)[#station-token]
    ]
  ],
)
#v(3mm)

#let qr-cell(img-path, label-de, label-en, url-text) = rect(
  width: 100%,
  stroke: 1pt + gpn-orange,
  fill: gpn-white,
  inset: 5mm,
  grid(
    columns: (32mm, 1fr),
    column-gutter: 5mm,
    align: (center + horizon, left + horizon),
    image(img-path, width: 32mm, height: 32mm, fit: "contain"),
    [
      #text(size: 13pt, weight: "bold")[#label-de]
      #v(0.5mm)
      #text(size: 10.5pt)[#label-en]
      #v(2mm)
      #text(size: 10.5pt)[#url-text.replace("https://", "").split("/").at(0)]
    ],
  ),
)

#grid(
  columns: (57%, 1fr),
  rows: (1fr),
  column-gutter: 14mm,

  [
    #if photo-path != "" {
      image(photo-path, width: 100%, height: 120mm, fit: "contain")
      v(5mm)
    }
    #text(size: 10.5pt, weight: "bold")[Nur die abgebildeten Personen sind berechtigt, Geld aus dieser Kasse zu entnehmen.]
    #v(1.5mm)
    #text(size: 9pt)[Only the persons shown in this photo are authorized to take money from this register.]
  ],

  [
    #v(1fr)
    #qr-cell(qr-cash-full,  "Kasse voll?",          "Cash register full?", url-cash-full)
    #v(10mm)
    #qr-cell(qr-change-req, "Wechselgeld benötigt?", "Change needed?",      url-change-req)
    #v(10mm)
    #qr-cell(qr-other,      "Sonstige Anliegen",     "Other requests",      url-other)
    #v(1fr)
  ],
)
