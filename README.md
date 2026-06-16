# 📝 CBT System (Computer-Based Testing)

Modern CBT (Computer-Based Testing) system untuk sekolah Indonesia. Dibangun dengan **Laravel 12** + **Bootstrap 5** + **MySQL**, dengan arsitektur service-based yang bersih dan mudah dikembangkan.

> **Diinspirasi dari** [garudacbt/cbt](https://github.com/garudacbt/cbt) — dibuat ulang dari nol dengan Laravel 12, full source code bersih (no obfuscation), no bugs, dan beberapa improvement penting.

## ✨ Fitur Utama

### 🧪 Tipe Soal (7 jenis)
- **Pilihan Ganda** (single answer)
- **Pilihan Ganda Kompleks** (multiple answers) — *improvement*
- **Benar / Salah**
- **Isian Singkat** (case-insensitive)
- **Essai** (manual grading)
- **Menjodohkan** (matching pairs, partial credit)
- **Mengurutkan** (ordering) — *improvement baru*

### 🎯 Pelaksanaan Ujian
- **Token-based access** dengan expiry time
- **Multi sesi** (multiple time slots per exam)
- **Multi ruang** dengan pengawas
- **Alokasi siswa per ruang** dengan nomor peserta
- **Auto-save** jawaban via AJAX
- **Timer countdown** real-time dengan auto-submit saat waktu habis
- **Anti-cheat detection**: deteksi tab switch, fullscreen exit
- **Resume** untuk attempt yang belum selesai
- **Max attempts** enforcement

### 📊 Penilaian Otomatis
- **Auto-grade** untuk PG, PG Kompleks, B/S, Isian Singkat, Menjodohkan, Urutan
- **Partial credit** untuk Menjodohkan (proporsional)
- **Manual grading** untuk Essai dengan form koreksi
- **Pass/fail** otomatis berdasarkan passing score
- **Real-time score calculation**

### 👥 Manajemen Data Master
- **Tahun Ajaran** (multi-year, semester Ganjil/Genap)
- **Mata Pelajaran** (dengan kelompok: Umum/Peminatan)
- **Kelas / Rombel** (per tahun ajaran)
- **Siswa** (CRUD lengkap + kelas assignment)
- **Guru** (CRUD lengkap)
- **Bank Soal** (per mata pelajaran, bisa dishare)

### 🎓 E-Learning (Improvement)
- **Materi** pembelajaran dengan attachment
- **Tugas** dengan deadline & late submission handling
- **Submission** tracking
- **Pengumuman** dengan target audience (all/students/teachers) & pinning

### 🔐 Security & RBAC
- **Spatie Permission** untuk role-based access
- **CSRF protection** di semua form
- **XSS prevention** via Blade auto-escape
- **Password hashing** (bcrypt cost 12)
- **Activity log** untuk audit trail
- **Mass assignment protection**
- **Session-based auth** dengan Breeze

## 🚀 Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | **Laravel 12.x** (PHP 8.3+) |
| Database | MySQL 8.0+ / MariaDB 10.6+ |
| Frontend | Bootstrap 5.3, Bootstrap Icons, Vanilla JS |
| Auth | Laravel Breeze + Spatie Permission |
| Activity Log | Spatie ActivityLog |
| PDF (opsional) | DomPDF |
| Testing | PHPUnit 11 |

## 📋 Requirements

- PHP **8.3** atau lebih tinggi
- Composer 2.x
- MySQL 8.0+ atau MariaDB 10.6+
- Node.js 18+ & NPM
- PHP Extensions: `pdo_mysql`, `mbstring`, `gd`, `zip`, `intl`

## 🚀 Instalasi

### 1. Clone & Install Dependencies

```bash
git clone https://github.com/hapizddcr/cbt.git
cd cbt
composer install
npm install
```

### 2. Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
APP_NAME="CBT System"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cbt
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 3. Buat Database

```sql
CREATE DATABASE cbt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Migrate & Seed

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
```

Seeder otomatis membuat:
- 2 roles: **admin** & **student**
- 20+ permissions granular
- User demo: admin & 1 siswa
- 6 mata pelajaran sample
- 1 tahun ajaran aktif
- 1 bank soal + 1 soal sample

### 5. Build & Run

```bash
npm run build
php artisan serve
```

Buka `http://localhost:8000`

## 👤 Akun Demo

| Email | Password | Role |
|-------|----------|------|
| `admin@cbt.local` | `password` | Admin |
| `siswa@cbt.local` | `password` | Student |

⚠️ **Ganti password setelah login pertama di production!**

## 🧪 Testing

```bash
php artisan test
```

**42 tests, 91 assertions** mencakup:
- ✅ Token validation
- ✅ Exam attempt lifecycle (start/save/submit/resume)
- ✅ Auto-grading untuk semua tipe soal
- ✅ Max attempts enforcement
- ✅ Authorization (cross-student access)
- ✅ Authentication & profile

## 🏗️ Arsitektur

```
app/
├── Http/Controllers/
│   ├── Admin/             # Admin: exams, questions, sessions, grading
│   ├── Student/           # Student: exam taking, dashboard
│   └── Auth/              # Login, register, profile
├── Models/                # 18 Eloquent models dengan relasi
│   ├── Exam, ExamSession, ExamAttempt, ExamAnswer, ExamToken
│   ├── Question, QuestionOption, QuestionMatchingPair, QuestionOrderingItem
│   ├── Subject, Classroom, Student, Teacher, AcademicYear
│   └── ...
├── Services/
│   └── ExamService.php    # Core exam engine: token, attempt, grading
└── Providers/
```

### Design Patterns

- **Service Layer Pattern**: Business logic diisolasi di `ExamService`
- **Cast JSON for flexible answer_data**: Support untuk berbagai tipe jawaban
- **Soft Deletes**: Untuk audit trail & recovery
- **Activity Logging**: Spatie ActivityLog di model penting
- **Form Request Validation**: Validation terpisah dari controller
- **Policy & Middleware**: RBAC via Spatie middleware

## 🗺️ Roadmap

- [ ] Analisis soal otomatis (tingkat kesukaran, daya pembeda)
- [ ] Export nilai ke Excel
- [ ] Cetak kartu ujian & daftar hadir
- [ ] Mobile app (Flutter)
- [ ] Real-time monitoring via WebSocket
- [ ] Integrasi dengan Dapodik
- [ ] Rapor digital

## 📝 License

MIT License — bebas digunakan untuk komersial & non-komersial.

## 🙏 Credits

- Inspired by [garudacbt/cbt](https://github.com/garudacbt/cbt) — the original CodeIgniter 3 version
- Built with [Laravel](https://laravel.com)
- UI by [Bootstrap](https://getbootstrap.com)
- Icons by [Bootstrap Icons](https://icons.getbootstrap.com)

## 📧 Support

- Issues: [GitHub Issues](https://github.com/hapizddcr/cbt/issues)
- Email: hapizddcr@gmail.com

---

**Dibangun dengan ❤️ untuk pendidikan Indonesia**
