<?php

/**
 * Cerez Politikasi taslagi.
 *
 * Sitede su an YALNIZCA zorunlu cerezler kullaniliyor (oturum, sepet,
 * CSRF, dil ve para birimi tercihi). Analitik veya reklam cerezi EKLENIRSE
 * bu metin guncellenmeli ve ayrica acik riza (onay bandi) alinmali.
 *
 * TASLAKTIR; yayina almadan once hukuk danismanina okutulmalidir.
 */
return [
    'slug' => 'cerez-politikasi',
    'footer' => true,
    'title' => [
        'tr' => 'Çerez Politikası',
        'en' => 'Cookie Policy',
        'bg' => 'Политика за бисквитките',
        'el' => 'Πολιτική Cookies',
    ],
    'body' => [
        'tr' => <<<'TR'
1. ÇEREZ NEDİR?

Çerez, bir web sitesini ziyaret ettiğinizde tarayıcınıza kaydedilen küçük bir metin dosyasıdır. Sitenin sizi hatırlamasını, sepetinizin kaybolmamasını ve oturumunuzun güvenli kalmasını sağlar.

2. BU SİTEDE KULLANILAN ÇEREZLER

ZORUNLU ÇEREZLER — sitenin çalışması için gereklidir, kapatılamaz:

Oturum çerezi: tarayıcınıza yalnızca bir oturum numarası yazar. Giriş yaptıysanız hesabınızın açık kalmasını, üye olmadan alışveriş yapıyorsanız sepetinizin sayfalar arasında korunmasını ve seçtiğiniz para biriminin hatırlanmasını bu numara sağlar. Bilgilerin kendisi sunucumuzda tutulur, çerezin içinde taşınmaz. Tarayıcıyı kapattığınızda veya oturum süresi dolduğunda silinir.
Güvenlik (CSRF) çerezi: formların sizin adınıza sahte isteklerle gönderilmesini engeller.

Seçtiğiniz DİL çerezde tutulmaz; adresin kendisinde yer alır (/tr/, /en/, /bg/, /el/).

Bu çerezler kimliğinizi tespit etmek veya davranışınızı izlemek için kullanılmaz.

İSTEĞE BAĞLI ÇEREZLER — yalnızca onay vermeniz hâlinde çalışır:

Analitik çerezler: hangi sayfaların kaç kez görüntülendiğini topluca ölçer.
Pazarlama çerezleri: reklam gösterimlerinin ölçümlenmesinde kullanılır.

Bu politikanın yayımlandığı tarihte sitede isteğe bağlı çerez KULLANILMAMAKTADIR. Eklenmesi hâlinde bu metin güncellenecek ve onayınız ayrıca istenecektir.

3. HUKUKİ DAYANAK

Zorunlu çerezler, 6698 sayılı Kanun'un 5/2-f maddesindeki meşru menfaat kapsamında, açık rıza aranmaksızın kullanılır. İsteğe bağlı çerezler yalnızca açık rızanızla (5/1) çalışır.

4. ÇEREZLERİ NASIL YÖNETİRSİNİZ?

Tarayıcınızın ayarlarından çerezleri silebilir veya engelleyebilirsiniz:

Chrome: Ayarlar > Gizlilik ve güvenlik > Üçüncü taraf çerezleri
Firefox: Ayarlar > Gizlilik ve Güvenlik > Çerezler ve Site Verileri
Safari: Tercihler > Gizlilik > Çerezleri ve web sitesi verilerini yönet
Edge: Ayarlar > Çerezler ve site izinleri

Zorunlu çerezleri engellerseniz sepet, giriş ve ödeme adımları ÇALIŞMAZ.

5. İLETİŞİM

Çerez kullanımına ilişkin sorularınız için [[eposta]] adresine yazabilirsiniz. Kişisel verilerinizin işlenmesine dair ayrıntılı bilgi KVKK Aydınlatma Metni sayfasındadır.
TR,
        'en' => <<<'EN'
1. WHAT IS A COOKIE?

A cookie is a small text file saved to your browser when you visit a website. It allows the site to remember you, keeps your basket from being lost and keeps your session secure.

2. COOKIES USED ON THIS SITE

ESSENTIAL COOKIES — required for the site to work and cannot be switched off:

Session cookie: stores nothing but a session identifier in your browser. That identifier is what keeps you signed in, preserves your basket between pages when shopping without an account, and remembers the currency you chose. The information itself is held on our server, not inside the cookie. Deleted when you close the browser or the session expires.
Security (CSRF) cookie: prevents forms being submitted on your behalf by forged requests.

Your chosen LANGUAGE is not stored in a cookie; it is part of the address itself (/tr/, /en/, /bg/, /el/).

These cookies are not used to identify you or to track your behaviour.

OPTIONAL COOKIES — only active if you consent:

Analytics cookies: measure in aggregate how often pages are viewed.
Marketing cookies: used to measure advertising impressions.

As at the date of this policy, NO optional cookies are used on this site. Should any be added, this text will be updated and your consent will be requested separately.

3. LEGAL BASIS

Essential cookies are used on the basis of legitimate interest under article 5/2-f of Law No. 6698, without requiring explicit consent. Optional cookies operate only with your explicit consent (article 5/1).

4. MANAGING COOKIES

You can delete or block cookies from your browser settings:

Chrome: Settings > Privacy and security > Third-party cookies
Firefox: Settings > Privacy & Security > Cookies and Site Data
Safari: Preferences > Privacy > Manage website data
Edge: Settings > Cookies and site permissions

If you block essential cookies, the basket, sign-in and checkout steps WILL NOT WORK.

5. CONTACT

For questions about our use of cookies, write to [[eposta]]. Detailed information on the processing of your personal data is in our Personal Data Protection Notice.

This English text is provided for information. In the event of any discrepancy, the Turkish version prevails.
EN,
        'bg' => <<<'BG'
1. КАКВО Е БИСКВИТКА?

Бисквитката е малък текстов файл, който се запазва в браузъра ви при посещение на уебсайт. Позволява на сайта да ви запомни, да запази кошницата ви и да поддържа сесията ви сигурна.

2. БИСКВИТКИ, ИЗПОЛЗВАНИ НА ТОЗИ САЙТ

ЗАДЪЛЖИТЕЛНИ БИСКВИТКИ — необходими за работата на сайта и не могат да бъдат изключени:

Бисквитка за сесия: записва в браузъра ви единствено номер на сесия. Този номер поддържа входа ви в профила, запазва кошницата ви между страниците при пазаруване без регистрация и помни избраната валута. Самите данни се съхраняват на нашия сървър, а не в бисквитката. Изтрива се при затваряне на браузъра или при изтичане на сесията.
Бисквитка за сигурност (CSRF): предотвратява изпращането на формуляри от ваше име чрез подправени заявки.

Избраният ЕЗИК не се съхранява в бисквитка; той е част от самия адрес (/tr/, /en/, /bg/, /el/).

Тези бисквитки не се използват за идентифициране или проследяване на поведението ви.

НЕЗАДЪЛЖИТЕЛНИ БИСКВИТКИ — работят само при дадено съгласие:

Аналитични бисквитки: измерват обобщено колко пъти са разглеждани страниците.
Маркетингови бисквитки: използват се за измерване на рекламни импресии.

Към датата на тази политика на сайта НЕ СЕ използват незадължителни бисквитки. При добавяне текстът ще бъде актуализиран и съгласието ви ще бъде поискано отделно.

3. ПРАВНО ОСНОВАНИЕ

Задължителните бисквитки се използват на основание легитимен интерес по чл. 5/2-е от Закон № 6698, без да се изисква изрично съгласие. Незадължителните работят само с вашето изрично съгласие (чл. 5/1).

4. УПРАВЛЕНИЕ НА БИСКВИТКИТЕ

Можете да изтривате или блокирате бисквитки от настройките на браузъра си:

Chrome: Настройки > Поверителност и сигурност > Бисквитки на трети страни
Firefox: Настройки > Поверителност и сигурност > Бисквитки и данни за сайтове
Safari: Предпочитания > Поверителност > Управление на данните на уебсайтове
Edge: Настройки > Бисквитки и разрешения за сайтове

Ако блокирате задължителните бисквитки, кошницата, входът и плащането НЯМА ДА РАБОТЯТ.

5. КОНТАКТ

За въпроси относно бисквитките пишете на [[eposta]]. Подробна информация за обработването на личните ви данни ще намерите в Уведомлението за защита на личните данни.

Този текст на български език е с информативен характер. При несъответствие предимство има турската версия.
BG,
        'el' => <<<'EL'
1. ΤΙ ΕΙΝΑΙ ΤΟ COOKIE;

Το cookie είναι ένα μικρό αρχείο κειμένου που αποθηκεύεται στο πρόγραμμα περιήγησής σας όταν επισκέπτεστε έναν ιστότοπο. Επιτρέπει στον ιστότοπο να σας θυμάται, διατηρεί το καλάθι σας και κρατά ασφαλή τη συνεδρία σας.

2. COOKIES ΠΟΥ ΧΡΗΣΙΜΟΠΟΙΟΥΝΤΑΙ ΣΕ ΑΥΤΟΝ ΤΟΝ ΙΣΤΟΤΟΠΟ

ΑΠΑΡΑΙΤΗΤΑ COOKIES — αναγκαία για τη λειτουργία και δεν απενεργοποιούνται:

Cookie συνεδρίας: αποθηκεύει στο πρόγραμμα περιήγησης μόνο έναν αριθμό συνεδρίας. Αυτός ο αριθμός διατηρεί τη σύνδεσή σας, κρατά το καλάθι σας μεταξύ σελίδων όταν ψωνίζετε χωρίς λογαριασμό και θυμάται το νόμισμα που επιλέξατε. Τα ίδια τα δεδομένα τηρούνται στον διακομιστή μας, όχι μέσα στο cookie. Διαγράφεται με το κλείσιμο του προγράμματος περιήγησης ή τη λήξη της συνεδρίας.
Cookie ασφαλείας (CSRF): αποτρέπει την υποβολή φορμών εξ ονόματός σας με πλαστά αιτήματα.

Η ΓΛΩΣΣΑ που επιλέγετε δεν αποθηκεύεται σε cookie· αποτελεί μέρος της ίδιας της διεύθυνσης (/tr/, /en/, /bg/, /el/).

Τα cookies αυτά δεν χρησιμοποιούνται για ταυτοποίηση ή παρακολούθηση της συμπεριφοράς σας.

ΠΡΟΑΙΡΕΤΙΚΑ COOKIES — λειτουργούν μόνο με τη συγκατάθεσή σας:

Cookies ανάλυσης: μετρούν συγκεντρωτικά πόσες φορές προβάλλονται οι σελίδες.
Cookies μάρκετινγκ: χρησιμοποιούνται για τη μέτρηση διαφημιστικών εμφανίσεων.

Κατά την ημερομηνία της παρούσας πολιτικής ΔΕΝ χρησιμοποιούνται προαιρετικά cookies. Σε περίπτωση προσθήκης, το κείμενο θα επικαιροποιηθεί και θα ζητηθεί χωριστά η συγκατάθεσή σας.

3. ΝΟΜΙΚΗ ΒΑΣΗ

Τα απαραίτητα cookies χρησιμοποιούνται βάσει έννομου συμφέροντος κατά το άρθρο 5/2-στ του Νόμου 6698, χωρίς ρητή συγκατάθεση. Τα προαιρετικά λειτουργούν μόνο με ρητή συγκατάθεση (άρθρο 5/1).

4. ΔΙΑΧΕΙΡΙΣΗ COOKIES

Μπορείτε να διαγράψετε ή να αποκλείσετε cookies από τις ρυθμίσεις του προγράμματος περιήγησης:

Chrome: Ρυθμίσεις > Απόρρητο και ασφάλεια > Cookies τρίτων
Firefox: Ρυθμίσεις > Απόρρητο και ασφάλεια > Cookies και δεδομένα ιστότοπων
Safari: Προτιμήσεις > Απόρρητο > Διαχείριση δεδομένων ιστότοπων
Edge: Ρυθμίσεις > Cookies και δικαιώματα ιστότοπων

Αν αποκλείσετε τα απαραίτητα cookies, το καλάθι, η σύνδεση και η πληρωμή ΔΕΝ ΘΑ ΛΕΙΤΟΥΡΓΟΥΝ.

5. ΕΠΙΚΟΙΝΩΝΙΑ

Για ερωτήσεις σχετικά με τα cookies γράψτε στο [[eposta]]. Αναλυτικές πληροφορίες για την επεξεργασία των προσωπικών σας δεδομένων θα βρείτε στην Ενημέρωση για την Προστασία Προσωπικών Δεδομένων.

Το ελληνικό κείμενο παρέχεται για ενημέρωση. Σε περίπτωση απόκλισης υπερισχύει το τουρκικό.
EL,
    ],
];
