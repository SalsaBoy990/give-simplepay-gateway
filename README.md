# SimplePay Fizetési Gateway a GiveWP Donations Plugin-hoz

## Bevezető

A SimplePay API v2 használatával lett implementálva.

A kártyaadatok a kereskedő oldalán vannak megadva, és a SimplePay az ott megadott adatokkal indít tranzakciót.
Ebben az esetben nem történik átirányítás a SimplePay fizető oldalára, hanem az Adományozás gombra kattintva beküldésre kerül a POST request és a SimplePay a tranzakció elvégzése után visszatér a válasszal (sikeres vagy sikertelen tranzakció).

Dokumentáció, fizetés kártyaadatokkal (ALU logika): https://simplepay.hu/fejlesztoknek/

NAGYON FONTOS:

> 1.2 Kártyaadat kezelés kereskedői feltételei
> A kártyaadatok biztonságát a SimplePay biztonságos fizetőoldalán a PCI-DSS (Payment Card
> Industry Data Security Standard) megfelelőség garantálja.
> A SimplePay rendszere lehetőséget biztosít arra, hogy a kereskedő irányából kártyaadatokat
> fogadjon be, azaz a kereskedő kezelje a vásárló kártyaadatait. Ebben az esetben a kereskedő
> a saját weboldalán kéri be a kártya adatokat a vásárlótól, majd az erre a célra használható
> külön SimplePay API interface (auto) használatával tudja elindítani a tranzakciót. Átirányítás
> ilyenkor nem történik, a kommunikáció a SimplePay rendszere felé a háttérben zajlik le.
> Az interface használata azonban a korábbiakhoz képest további feltételeket támaszt a
> kereskedővel szemben:
> **A kereskedői rendszernek is auditált PCI-DSS megfelelőséggel kell rendelkezzen.**

## A plugin jellemzői

- a PHP SDK mintakód alapján lett megvalósítva (`/simplepay/src/` mappában)

- Csak "CIT tranzakció" lehetséges, vagyis amikor egyszeri fizetésre kerül sor a felhasználó jelenlétével és aktív közreműködésével. (CIT = Customer Initiated Transaction)

> threeDSReqAuthMethod
> A vásárló regisztrációjának módja a kereskedői rendszerben:
> lehetséges értékek:
> 01: vendég

- Csak vendég felhasználók fizetnek.

## SimplePay által a kereskedőkkel szemben támasztott feltételeknek való megfelelés

### 1. Logók és tájékoztatók

> A fizetési elfogadóhely állandóan látható részén (pl. a láblécen), vagy a fizetés
> kiválasztásakor a tranzakciónál szükséges megjeleníteni a SimplePay logót.

A fizetésnél jelenik meg a logó és a logó egyben link is a SimplePay fizetési tájékoztatóra. Sőt, szöveges link is van.

### 2. Adattovábbítási nyilatkozat

A fizetésnél közvetlenül jelenik meg és kötelező checkbox-ként, ami nélkül nem lehet beküldeni az űrlapot.

A sablonszöveg is elhelyezésre kerül a linkkel, kitöltve:

> Tudomásul veszem, hogy a(z) [Kereskedő cégneve] ([székhelye]) adatkezelő által a(z)
> [Fizetési Elfogadóhely webcíme] felhasználói adatbázisában tárolt alábbi személyes
> adataim átadásra kerülnek az OTP Mobil Kft., mint adatfeldolgozó részére. Az
> adatkezelő által továbbított adatok köre az alábbi: [Kereskedő által továbbított
> adatok megnevezése]
> Az adatfeldolgozó által végzett adatfeldolgozási tevékenység jellege és célja a
> SimplePay Adatkezelési tájékoztatóban, az alábbi linken tekinthető meg:
> http://simplepay.hu/vasarlo-aff

### 3. Sikeres bankkártyás fizetés tájékoztató

A sikeres fizetés oldalon megjelenítjük a tranzakció sikeres állapotát a SimplePay tranzakció azonosítóval együtt.

### 4. Sikertelen bankkártyás fizetés tájékoztató

Sikertelen fizetés esetén a rendszer visszairányít a fizetési űrlaphoz, és ott jelenítjük meg értesítés formájában,
hogy a SimplePay tranzakció hibába ütközött a megadott sablon szerint eljárva:

> Sikertelen tranzakció.
> SimplePay tranzakció azonosító: 1xxxxxxxx
> Kérjük, ellenőrizze a tranzakció során megadott adatok helyességét.
> Amennyiben minden adatot helyesen adott meg, a visszautasítás
> okának kivizsgálása érdekében kérjük, szíveskedjen kapcsolatba lépni
> kártyakibocsátó bankjával.

A sikeres és a sikertelen tranzakcióról való értesítéseken felül továbbiakra nincs szükség esetünkben.

## Teszteléshez

Helyes kártyaszám: 4908366099900425

Hibás kártyaszám (nincs fedezet): 4111111111111111

## Wordpress sablon

Szükség van az alábbi GiveWP sablon felülírására:
`src/web/wp-content/plugins/give/templates/shortcode-receipt.php`

A Wordpress child sablonban kell felülírni:
Példa: `src/web/wp-content/themes/THEME-NAME/give/shortcode-receipt.php`
