# Widerrufsbutton fuer Magento 2

> Magento 2 Erweiterung zur Umsetzung des EU-Widerrufsrechts per Button-Klick.
> Entwickelt von **Zwernemann Medienentwicklung**.

---

## Worum geht es?

Die EU-Richtlinie **(EU) 2023/2673** schreibt vor, dass Verbraucher Online-Kaufvertraege kuenftig genauso einfach widerrufen koennen muessen, wie sie abgeschlossen wurden. **Ab dem 19. Juni 2026** ist ein gut sichtbarer Widerrufsbutton in Online-Shops Pflicht.

Dieses Magento 2 Modul liefert genau das: Ihre Kunden koennen Bestellungen mit wenigen Klicks widerrufen -- direkt aus dem Kundenkonto oder ueber ein separates Formular fuer Gastbestellungen. Sie als Shopbetreiber behalten dabei den vollen Ueberblick im Adminbereich.

---

## Was macht das Modul?

### Fuer Ihre Kunden

**Widerrufsbutton in der Bestelluebersicht**

In der Ansicht *Mein Konto > Meine Bestellungen* erscheint pro Bestellung eine neue Spalte. Dort sieht der Kunde auf einen Blick:

- Einen **Widerrufs-Link**, solange die Frist laeuft
- Den Hinweis **"Widerruf eingereicht"**, falls bereits widerrufen wurde
- Den Hinweis **"Frist abgelaufen"**, wenn die Widerrufsfrist verstrichen ist

Zusaetzlich wird auf der Bestelldetailseite ein **"Bestellung widerrufen"**-Button angezeigt.

**Widerrufs-Detailseite**

Vor dem eigentlichen Widerruf sieht der Kunde eine Zusammenfassung seiner Bestellung:

- Bestellnummer, Datum, Status, Gesamtbetrag
- Alle bestellten Positionen mit Name, Artikelnummer, Menge und Preis
- Bis wann der Widerruf moeglich ist (berechnet ab Versanddatum der letzten Lieferung)
- Einen Button zum endgueltigen Absenden -- mit vorgeschalteter Sicherheitsabfrage

**Gastbestellungen**

Kunden, die ohne Kundenkonto bestellt haben, erreichen den Widerruf ueber ein eigenes Suchformular. Dort genuegen Bestellnummer und E-Mail-Adresse, um die Bestellung zu finden und den Widerruf einzuleiten.

Erreichbar unter: `/withdrawal/guest/search`

**Bestaetigungsseite**

Nach dem Absenden wird der Kunde auf eine Erfolgsseite weitergeleitet. Dort wird bestaetigt, dass der Widerruf eingegangen ist und eine E-Mail unterwegs ist.

### Fuer Sie als Shopbetreiber

**Admin-Uebersicht aller Widerrufe**

Unter *Verkaeufe > Withdrawals* finden Sie eine tabellarische Uebersicht saemtlicher eingegangener Widerrufe:

- ID, Bestellnummer, Kundenname, E-Mail
- Status (Ausstehend / Bestaetigt / Abgelehnt)
- Datum der Bestellung und Datum des Widerrufs
- Direktlink zur jeweiligen Bestellansicht

Alle Spalten sind filterbar und sortierbar.

**Automatische Benachrichtigung per E-Mail**

Sobald ein Widerruf eingeht, werden zwei E-Mails verschickt:

1. **An den Kunden** -- Bestaetigung mit Bestelldetails
2. **An Sie** -- Benachrichtigung mit allen relevanten Daten

Zusaetzlich erhalten Sie eine BCC-Kopie der Kundenmail. Die E-Mail-Vorlagen lassen sich im Admin anpassen.

**Vermerk in der Bestellung**

Jeder Widerruf wird automatisch als Kommentar in der Bestellhistorie hinterlegt. So ist auch in der Bestellansicht sofort ersichtlich, dass ein Widerruf vorliegt.

**Konfigurierbar**

Im Admin unter *Stores > Configuration > Sales > Withdrawal Settings*:

- Modul ein- und ausschalten
- Empfaenger-Adresse fuer Benachrichtigungen festlegen
- Widerrufsfrist in Tagen einstellen, gezaehlt ab Versanddatum der letzten Lieferung (Standard: 14)
- E-Mail-Absender und Vorlagen waehlen

### REST API

Widerrufseintraege lassen sich auch programmatisch abrufen:

```
GET /rest/V1/zwernemann/withdrawals
```

Zugriff ist per ACL-Berechtigung geschuetzt (`Zwernemann_Withdrawal::withdrawals`).

### Mehrsprachigkeit

Komplett uebersetzt in **Deutsch** und **Englisch** (97 Zeichenketten). Weitere Sprachen koennen ueber eigene CSV-Dateien ergaenzt werden.

---

## Systemvoraussetzungen

| Komponente | Version |
|---|---|
| Magento 2 Open Source | 2.4.6 bis 2.4.8-p1 |
| PHP | 7.4 oder hoeher |

Sie nutzen eine andere Magento-Version? Lassen Sie es uns wissen -- wir testen gerne.

---

## Installation

### Per ZIP-Datei

1. Entpacken Sie die ZIP-Datei und kopieren Sie den gesamten Inhalt nach:

   ```
   app/code/Zwernemann/Withdrawal/
   ```

   Kontrollieren Sie, dass die Struktur so aussieht:

   ```
   app/code/Zwernemann/Withdrawal/
       Api/
       Block/
       Controller/
       Helper/
       Model/
       Ui/
       etc/
       i18n/
       view/
       composer.json
       registration.php
   ```

2. Fuehren Sie folgende Befehle im Magento-Root aus:

   ```bash
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento setup:static-content:deploy de_DE en_US
   php bin/magento cache:flush
   ```

3. Pruefen Sie, ob das Modul aktiv ist:

   ```bash
   php bin/magento module:status Zwernemann_Withdrawal
   ```

### Per Composer

```bash
composer require zwernemann/module-withdrawal
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy de_DE en_US
php bin/magento cache:flush
```

---

## Einrichtung

1. Im Magento Admin einloggen
2. Zu **Stores > Configuration > Sales > Withdrawal Settings** navigieren
3. **Modul aktivieren** auf *Ja* setzen
4. **Benachrichtigungs-E-Mail** eintragen -- hierhin gehen die Widerrufs-Meldungen
5. **Widerrufsfrist** anpassen, falls die gesetzliche Frist abweicht
6. Bei Bedarf E-Mail-Absender und Vorlagen konfigurieren
7. Speichern und Cache leeren

### Gastbestellungs-Formular verlinken

Das Suchformular fuer Gastbestellungen liegt unter:

```
https://www.ihr-shop.de/withdrawal/guest/search
```

Binden Sie diesen Link z.B. hier ein:

- Im Footer Ihres Shops
- In Bestellbestaetigungs-E-Mails
- Auf Ihrer Widerrufsbelehrungs-Seite

Mit Magento URL-Rewrites koennen Sie die Adresse beliebig anpassen, etwa auf `/widerruf`.

---

## Deinstallation

```bash
php bin/magento module:disable Zwernemann_Withdrawal
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

Danach das Verzeichnis `app/code/Zwernemann/Withdrawal/` loeschen.

Die Datenbanktabelle `zwernemann_withdrawal` bleibt erhalten und kann bei Bedarf manuell entfernt werden.

---

## Versionshistorie

### 1.5.0
Neu hinzugefügte Sprachen: Bulgarisch, Dänisch, Estnisch, Finnisch, Französisch, Griechisch, Irisch, Italienisch, Kroatisch, Lettisch, Litauisch, Maltesisch, Niederländisch, Polnisch, Portugiesisch, Rumänisch, Schwedisch, Slowakisch, Slowenisch, Spanisch, Tschechisch, Ungarisch. Das Modul unterstützt jetzt alle 24 offiziellen Amtssprachen der Europäischen Union. Alle Übersetzungen verwenden den juristisch korrekten Begriff für das gesetzliche Widerrufsrecht gemäß EU-Verbraucherrechterichtlinie (2011/83/EU).

### 1.4.0

- Deleted the version attribute from composer.json. Composer has great integration with version control systems Git and there is no need to manually track version numbers in a text file for Composer at all. The field only exists for special situations where a version control system is not in use.

### 1.3.0

- Admin can now confirm or reject individual withdrawal requests directly from the grid
- Context-sensitive action links per row (Confirm / Reject) — only shown when a status change makes sense
- Bulk actions to confirm or reject multiple withdrawal requests at once
- Added getById() and updateStatus() methods to WithdrawalRepositoryInterface and WithdrawalRepository

### 1.2.0

- Widerrufsfrist beginnt nun ab dem Versanddatum der letzten Lieferung statt ab Bestelleingang (gesetzlich korrekt gemaess EU-Richtlinie 2011/83/EU)
- Bei noch nicht versandten Bestellungen ist der Widerruf immer moeglich
- Fristanzeige entsprechend aktualisiert

### 1.1.0

- Kompletter Widerrufs-Workflow fuer eingeloggte Kunden und Gastbestellungen
- Widerrufsbutton in der Bestelluebersicht und auf der Bestelldetailseite
- Detailseite mit Bestellzusammenfassung und Fristanzeige
- Bestaetigungsseite nach erfolgreichem Widerruf
- E-Mail-Benachrichtigungen an Kunde und Shopbetreiber (inkl. BCC)
- Admin-Grid mit Filter, Sortierung, Paging und Direktlink zur Bestellung
- Konfigurationsbereich fuer Modul, Fristen und E-Mail-Einstellungen
- ACL-gestuetzte Berechtigungen und abgesicherte REST API
- CSRF-Schutz und JavaScript-Bestaetigungsdialog
- Vollstaendige Uebersetzungen DE/EN

### 1.0.3

- Widerruf fuer Gastbestellungen ermoeglicht
- Erfolgsseite nach Absenden des Widerrufs

### 1.0.2

- Spalte "Bestellung aufgegeben am" im Admin-Grid
- Aktion "Bestellung ansehen" im Admin-Grid
- Automatischer Kommentar in der Bestellhistorie

### 1.0.1

- Shop-E-Mail als BCC in der Bestaetigungsmail
- Bestelldetails ueber dem Widerrufsformular

### 1.0.0

- Erstveroeffentlichung
- Getestet mit Magento 2.4.6 bis 2.4.8-p1

---

## Geplant

- Hyva-Theme-Kompatibilitaet pruefen und sicherstellen
- REST API um Schreibzugriffe erweitern
- Individuelle Widerrufsfristen pro Produkt (ueber Produktattribute)

---

## Kontakt & Support

**Zwernemann Medienentwicklung**\
Martin Zwernemann\
79730 Murg

[Zur Website](https://www.zwernemann.de/widerrufsbutton-fuer-magento-2/)

Bei Fragen, Problemen oder Ideen fuer neue Funktionen -- melden Sie sich gerne.

---

## Lizenz

OSL-3.0
