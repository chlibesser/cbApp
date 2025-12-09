# Validierungsrichtlinien - cbApp V1

## 🎯 Grundprinzip: Backend-Only Validation

**WICHTIG**: In cbApp V1 wird ausschließlich serverseitige Validierung durchgeführt.

## 🚫 Frontend-Validierung

- **KEINE Frontend-Validierung** implementieren
- **KEINE** Client-seitigen Validierungsregeln (z.B. Vue/Vuetify Validatoren)
- **KEINE** JavaScript-basierte Formular-Validierung
- Das Frontend sammelt nur Eingaben und sendet sie an das Backend

## ✅ Backend-Validierung

- **Alle Validierung** erfolgt ausschließlich im Laravel Backend
- Verwendung von Laravel Request Validation
- Validierungsregeln werden in Form Requests oder direkt im Controller definiert
- Vollständige Validierung aller Eingabedaten vor Verarbeitung

## 🇩🇪 Fehlermeldungen

### Sprache
- **Alle Fehlermeldungen** müssen auf **Deutsch** angezeigt werden
- Keine englischen Validierungsfehlermeldungen für Endbenutzer
- Deutsche Übersetzungen für Laravel Validation Messages

### Anzeige
- **Alle Backend-Validierungsfehler** müssen im Frontend angezeigt werden
- Fehlermeldungen sollen benutzerfreundlich und verständlich sein
- Konkrete Hinweise auf das Problem und Lösungsansätze

### Implementierung
- Frontend empfängt Validierungsfehler vom Backend (422 Status)
- Mapping der Backend-Fehlermeldungen auf Frontend-Komponenten
- Einheitliche Darstellung von Fehlermeldungen in der gesamten Anwendung

## 🍞 Toast-Benachrichtigungen

### Bei jeder Daten-Transaktion
- **IMMER** Toast-Nachrichten bei Datenoperationen anzeigen
- Gilt für: Erstellen, Aktualisieren, Löschen von Datensätzen
- Sowohl bei **Erfolg** als auch bei **Fehlern**

### Erfolgs-Toasts
- **Grüne Toast-Nachricht** bei erfolgreichem Speichern/Aktualisieren/Löschen
- Deutsche Bestätigungsmeldungen:
  - "Datensatz erfolgreich gespeichert"
  - "Änderungen erfolgreich gespeichert"
  - "Datensatz erfolgreich gelöscht"
  - "Daten erfolgreich aktualisiert"

### Fehler-Toasts  
- **Rote Toast-Nachricht** bei Fehlern oder Validierungsproblemen
- Deutsche Fehlermeldungen:
  - "Fehler beim Speichern der Daten"
  - "Validierungsfehler - bitte prüfen Sie Ihre Eingaben"
  - "Fehler beim Löschen des Datensatzes"
  - "Verbindungsfehler - bitte versuchen Sie es erneut"

### Toast-Verhalten
- **Automatisches Ausblenden** nach 4-5 Sekunden
- **Manuell schließbar** durch Klick auf X-Button
- **Eindeutige Positionierung** (z.B. oben rechts)
- **Nicht überlappend** - neue Toasts schieben alte nach unten

## 📋 Beispiel-Workflow

1. **User** füllt Formular aus und klickt "Senden"
2. **Frontend** sendet Daten ohne Validierung an Backend API
3. **Backend** validiert alle Eingaben mit Laravel Validation
4. **Bei Fehlern**: 
   - Backend sendet 422 mit deutschen Fehlermeldungen
   - Frontend zeigt Validierungsfehler an Formularfeldern an
   - **Roter Toast**: "Validierungsfehler - bitte prüfen Sie Ihre Eingaben"
5. **Bei Erfolg**: 
   - Backend verarbeitet Daten und sendet Erfolgsantwort
   - **Grüner Toast**: "Datensatz erfolgreich gespeichert"

## 🛡️ Sicherheit

- Client-seitige Validierung bietet **keine Sicherheit**
- Alle sicherheitskritischen Prüfungen nur im Backend
- Assumption: Alle Frontend-Eingaben sind potentiell manipuliert

---

**Merksatz**: "Traue niemals dem Frontend - validiere alles im Backend!"