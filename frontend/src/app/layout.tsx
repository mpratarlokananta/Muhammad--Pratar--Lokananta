import type { Metadata } from "next";
import localFont from "next/font/local";
import "./globals.css";
import ClientWrapper from "@/components/ClientWrapper";

const monument = localFont({
  src: [
    {
      path: "../../public/fonts/MonumentExtended-Ultrabold.otf",
      weight: "900",
      style: "normal",
    },
  ],
  variable: "--font-monument",
});

const siteUrl = "https://mpratarlokananta.vercel.app";
const siteName = "Muhammad Pratar Lokananta";
const siteDescription =
  "Front End Developer portfolio — crafting clean and responsive interfaces with React, Next.js, and modern web technologies. Explore projects, certificates, and get in touch.";

export const metadata: Metadata = {
  metadataBase: new URL(siteUrl),
  title: {
    default: `${siteName} | Front End Developer`,
    template: `%s | ${siteName}`,
  },
  description: siteDescription,
  keywords: [
    "Muhammad Pratar Lokananta",
    "MLKNTA",
    "Front End Developer",
    "Next.js",
    "React",
    "Portfolio",
    "Web Developer Indonesia",
    "Pasuruan",
  ],
  authors: [{ name: "Muhammad Pratar Lokananta", url: siteUrl }],
  creator: "Muhammad Pratar Lokananta",
  publisher: "Muhammad Pratar Lokananta",
  robots: {
    index: true,
    follow: true,
    googleBot: {
      index: true,
      follow: true,
      "max-video-preview": -1,
      "max-image-preview": "large",
      "max-snippet": -1,
    },
  },
  openGraph: {
    type: "website",
    locale: "en_US",
    url: siteUrl,
    siteName,
    title: `${siteName} | Front End Developer`,
    description: siteDescription,
    images: [
      {
        url: "/og-image.jpg",
        width: 1200,
        height: 630,
        alt: siteName,
      },
    ],
  },
  twitter: {
    card: "summary_large_image",
    title: `${siteName} | Front End Developer`,
    description: siteDescription,
    images: ["/og-image.jpg"],
    creator: "@mfrdhn",
  },
  alternates: {
    canonical: siteUrl,
  },
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "Person",
    name: "Muhammad Pratar Lokananta",
    url: siteUrl,
    jobTitle: "Front End Developer",
    description: siteDescription,
    sameAs: [
      "https://github.com/mpratarlokananta",
      "https://www.linkedin.com/in/m-pratar-lokananta-530356399?utm_source=share_via&utm_content=profile&utm_medium=member_android",
      "https://www.instagram.com/loknnt?obrf=aWMwbW43YXZ3bmFr",
    ],
    address: {
      "@type": "PostalAddress",
      addressLocality: "Pasuruan",
      addressRegion: "East Java",
      addressCountry: "ID",
    },
  };

  return (
    <html lang="en" className={`${monument.variable} scroll-smooth`}>
      <head>
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
        />
      </head>
      <body className="antialiased bg-[#0a0a0a] text-white selection:bg-red-600 selection:text-white overflow-x-hidden">
        <ClientWrapper>
          {children}
        </ClientWrapper>
      </body>
    </html>
  );
}
