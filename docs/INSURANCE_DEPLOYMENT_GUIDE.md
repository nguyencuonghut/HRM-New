# Module Quản Lý Bảo Hiểm - Hướng Dẫn Triển Khai Production

## 📋 Tổng Quan

Tài liệu này hướng dẫn chi tiết quy trình triển khai Module Quản Lý Bảo Hiểm lên môi trường Production.

**Thời gian dự kiến**: 2-4 giờ (tùy quy mô dữ liệu)

**Thời điểm khuyến nghị**: Cuối tuần hoặc ngoài giờ làm việc

**Đội ngũ cần thiết**:
- 1 DevOps Engineer (lead)
- 1 Backend Developer (support)
- 1 DBA (database support)
- 1 QA Tester (verification)

---

## 🎯 Pre-Deployment Checklist

### 1. Code & Dependencies

- [ ] Code đã merge vào branch `main`/`production`
- [ ] Version tag đã tạo: `v1.0.0-insurance-module`
- [ ] Dependencies đã update trong `composer.json` và `package.json`
- [ ] `.env.production` đã cấu hình đầy đủ
- [ ] Secrets/credentials đã setup trên server

### 2. Database Preparation

- [ ] Backup database production hiện tại
  ```bash
  mysqldump -u user -p database > backup_pre_insurance_$(date +%Y%m%d_%H%M%S).sql
  ```
- [ ] Kiểm tra disk space: Cần ít nhất 500MB free
- [ ] Test migrations trên staging environment
- [ ] Verify indexes sẽ được tạo (20 indexes)
- [ ] Estimate migration time (~5-10 minutes)

### 3. Testing & Validation

- [ ] UAT đã hoàn tất với sign-off
- [ ] Performance test passed (77% improvement confirmed)
- [ ] Integrity check passed trên staging
- [ ] API endpoints tested
- [ ] Frontend build tested trên staging
- [ ] Browser compatibility verified (Chrome, Firefox, Edge)

### 4. Documentation

- [ ] User Guide đã gửi cho HR team
- [ ] Admin Guide đã gửi cho IT team
- [ ] Quick Start đã in và phân phát
- [ ] Training session đã schedule (sau deployment)
- [ ] Support team đã briefed

### 5. Infrastructure

- [ ] Server resources đủ (CPU, RAM, Disk)
- [ ] PHP version: >= 8.1
- [ ] MySQL version: >= 8.0
- [ ] Node.js version: >= 18.x
- [ ] Redis running (for cache)
- [ ] Monitoring tools ready (New Relic/Datadog/etc.)

### 6. Rollback Plan

- [ ] Rollback procedure documented
- [ ] Database backup verified (can restore)
- [ ] Previous version code tagged: `v0.9.9-pre-insurance`
- [ ] Rollback decision criteria defined
- [ ] Rollback team assigned

### 7. Communication

- [ ] Deployment announcement sent to users
- [ ] Maintenance window communicated (if downtime)
- [ ] Support channels prepared (#hrm-support Slack)
- [ ] Escalation contacts ready
- [ ] Post-deployment email template prepared

---

## 🚀 Deployment Steps

### Step 1: Maintenance Mode (Optional)

**Nếu cần downtime** (khuyến nghị: 30 phút):

```bash
# Bật maintenance mode
php artisan down --message="Đang nâng cấp Module BHXH. Dự kiến 30 phút." --retry=60

# Hoặc cho phép một số IPs vẫn truy cập
php artisan down --allow=123.45.67.89 --allow=98.76.54.32
```

**Nếu zero-downtime**: Skip bước này, nhưng cần cẩn thận với migrations

### Step 2: Pull Latest Code

```bash
# SSH vào production server
ssh user@production-server

# Navigate to project directory
cd /var/www/honghahrm

# Backup current code
cp -r . ../honghahrm_backup_$(date +%Y%m%d_%H%M%S)

# Pull latest code
git fetch origin
git checkout v1.0.0-insurance-module

# Hoặc pull từ main
git pull origin main
```

### Step 3: Install Dependencies

```bash
# Composer dependencies (skip dev)
composer install --no-dev --optimize-autoloader

# NPM dependencies
npm ci --production

# Clear old caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Step 4: Run Database Migrations

**QUAN TRỌNG**: Backup trước khi migrate!

```bash
# Verify backup exists
ls -lh backup_pre_insurance_*.sql

# Dry-run migrations (show SQL without executing)
php artisan migrate --pretend

# Review output, nếu OK thì chạy thực:
php artisan migrate --force

# Expected output:
# Migration table created successfully.
# Migrating: 2026_01_12_000000_create_insurance_tables
# Migrated:  2026_01_12_000000_create_insurance_tables (2.34s)
# Migrating: 2026_01_12_100000_add_insurance_performance_indexes
# Migrated:  2026_01_12_100000_add_insurance_performance_indexes (0.45s)
```

**Thời gian**: ~3-5 phút (tùy server performance)

### Step 5: Seed Initial Data

```bash
# Seed 5 insurance components (BHXH, BHYT, BHTN...)
php artisan db:seed --class=InsuranceComponentSeeder

# Verify seeded data
php artisan tinker
>>> \App\Models\InsuranceComponent::count();
=> 5
>>> exit
```

### Step 6: Run Data Backfill (If Needed)

**Nếu có hợp đồng hiện tại cần migrate sang hệ thống mới**:

```bash
# Tạo participations cho contracts đang active
php artisan insurance:backfill-participations

# Expected output:
# Scanning active contracts...
# Found 450 contracts without insurance participation.
# Creating participations...
# [========================================] 100%
# Successfully created 450 participations.
# Completed in 12.5 seconds.
```

**Chi tiết command này ở phần "Data Backfill Strategy" bên dưới**

### Step 7: Verify Data Integrity

```bash
# Chạy integrity check
php artisan insurance:check-integrity --detailed

# Expected: 0 issues
# Nếu có issues → investigate trước khi tiếp tục
```

### Step 8: Build Frontend Assets

```bash
# Build for production
npm run build

# Verify build output
ls -lh public/build/

# Expected: manifest.json + các file .js, .css với hash
```

### Step 9: Update Permissions & Roles

```bash
# Sync permissions to database
php artisan permission:sync

# Assign permissions to roles
php artisan tinker

# Gán quyền cho Payroll Admin role
>>> $role = \Spatie\Permission\Models\Role::findByName('payroll_admin');
>>> $role->givePermissionTo([
...   'view_insurance_reports',
...   'create_insurance_reports',
...   'update_insurance_reports',
...   'finalize_insurance_reports',
...   'export_insurance_reports',
...   'manage_insurance_components',
... ]);

# Gán quyền cho HR Employee role
>>> $hrRole = \Spatie\Permission\Models\Role::findByName('hr_employee');
>>> $hrRole->givePermissionTo([
...   'view_insurance_reports',
...   'view_insurance_details',
... ]);

>>> exit
```

### Step 10: Clear & Warm Caches

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Warm up caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

### Step 11: Restart Services

```bash
# Restart PHP-FPM
sudo systemctl restart php8.1-fpm

# Restart Queue Workers (if using)
sudo supervisorctl restart laravel-worker:*

# Restart Nginx/Apache
sudo systemctl restart nginx
# or
sudo systemctl restart apache2
```

### Step 12: Disable Maintenance Mode

```bash
php artisan up
```

### Step 13: Smoke Testing

**Ngay sau khi deploy**, test các chức năng chính:

```bash
# Test 1: Homepage loads
curl -I https://honghahrm.com
# Expected: HTTP/2 200

# Test 2: Login works
# → Đăng nhập qua browser

# Test 3: Insurance menu visible
# → Kiểm tra menu "Quản lý BHXH" có hiển thị không

# Test 4: API endpoints respond
curl -X GET https://honghahrm.com/api/insurance/components \
  -H "Authorization: Bearer YOUR_TOKEN"
# Expected: JSON response với 5 components

# Test 5: Can create test report
# → Thử tạo báo cáo test tháng
```

### Step 14: Run Benchmark (Performance Verification)

```bash
php artisan insurance:benchmark --employees=100

# Expected: All operations EXCELLENT/GOOD
# Nếu POOR → investigate
```

### Step 15: Monitor Initial Traffic

```bash
# Tail logs trong 30 phút đầu
tail -f storage/logs/laravel.log

# Monitor error rate
# (sử dụng monitoring tool: New Relic, Datadog...)

# Check MySQL slow queries
mysql> SHOW FULL PROCESSLIST;
```

---

## 📦 Data Backfill Strategy

### Khi Nào Cần Backfill?

**Cần backfill nếu**:
- Có hợp đồng cũ đang ACTIVE chưa có insurance participation
- Cần migrate dữ liệu BHXH từ hệ thống cũ

**Không cần nếu**:
- Hệ thống mới hoàn toàn, chưa có dữ liệu
- Quyết định bắt đầu từ tháng này (không cần lịch sử)

### Backfill Command

Tạo file: `app/Console/Commands/BackfillInsuranceParticipations.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\InsuranceComponent;
use App\Models\InsuranceParticipation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillInsuranceParticipations extends Command
{
    protected $signature = 'insurance:backfill-participations 
                            {--dry-run : Show what would be created without actually creating}
                            {--chunk=100 : Number of contracts to process per batch}';

    protected $description = 'Backfill insurance participations for existing active contracts';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');

        $this->info('🔍 Scanning active contracts...');

        // Find contracts without insurance participation
        $contractsQuery = Contract::query()
            ->where('status', 'ACTIVE')
            ->whereDoesntHave('insuranceParticipation')
            ->whereNotNull('insurance_salary'); // Chỉ backfill nếu có lương BHXH

        $totalContracts = $contractsQuery->count();

        if ($totalContracts === 0) {
            $this->info('✅ No contracts need backfilling.');
            return 0;
        }

        $this->info("📊 Found {$totalContracts} contracts without insurance participation.");

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No data will be created');
            $this->table(
                ['Contract ID', 'Employee', 'Insurance Salary', 'Start Date'],
                $contractsQuery->limit(10)->get()->map(fn($c) => [
                    $c->id,
                    $c->employee->name ?? 'N/A',
                    number_format($c->insurance_salary),
                    $c->start_date,
                ])
            );
            return 0;
        }

        if (!$this->confirm("Create participations for {$totalContracts} contracts?")) {
            $this->warn('❌ Aborted by user.');
            return 1;
        }

        // Get default components
        $defaultComponents = InsuranceComponent::where('is_enabled', true)
            ->get()
            ->keyBy('code');

        if ($defaultComponents->isEmpty()) {
            $this->error('❌ No insurance components found! Run seeder first.');
            return 1;
        }

        $this->info('🚀 Creating participations...');
        $bar = $this->output->createProgressBar($totalContracts);
        $bar->start();

        $created = 0;
        $errors = 0;

        $contractsQuery->chunk($chunkSize, function ($contracts) use ($defaultComponents, $bar, &$created, &$errors) {
            foreach ($contracts as $contract) {
                try {
                    DB::transaction(function () use ($contract, $defaultComponents, &$created) {
                        // Create participation
                        $participation = InsuranceParticipation::create([
                            'contract_id' => $contract->id,
                            'insurance_salary' => $contract->insurance_salary,
                            'status' => 'ACTIVE',
                            'started_at' => $contract->start_date,
                            'total_rate' => $defaultComponents->sum(fn($c) => $c->employee_rate + $c->employer_rate),
                        ]);

                        // Attach default components
                        foreach ($defaultComponents as $component) {
                            $participation->components()->create([
                                'component_id' => $component->id,
                                'employee_rate' => $component->employee_rate,
                                'employer_rate' => $component->employer_rate,
                                'is_fixed_amount' => false,
                            ]);
                        }

                        $created++;
                    });
                } catch (\Exception $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("Error processing contract {$contract->id}: {$e->getMessage()}");
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Successfully created {$created} participations.");
        if ($errors > 0) {
            $this->error("❌ Failed to create {$errors} participations. Check logs for details.");
        }

        // Verify
        $this->info('🔍 Running integrity check...');
        $this->call('insurance:check-integrity');

        return $errors > 0 ? 1 : 0;
    }
}
```

### Chạy Backfill

```bash
# Dry-run trước để xem sẽ tạo gì
php artisan insurance:backfill-participations --dry-run

# Review output, nếu OK thì chạy thật
php artisan insurance:backfill-participations

# Với batch size lớn hơn (nếu nhiều data)
php artisan insurance:backfill-participations --chunk=500
```

### Xử Lý Dữ Liệu Legacy

**Nếu có dữ liệu BHXH cũ từ Excel/System khác**:

1. **Export dữ liệu cũ** sang CSV với format:
   ```csv
   employee_id,insurance_salary,bhxh_hu_tri,bhxh_om_dau,bhxh_tnld,bhtn,bhyt
   NV001,15000000,1,1,1,1,1
   NV002,12000000,1,1,1,0,1
   ```

2. **Import vào hệ thống**:
   ```bash
   php artisan insurance:import-legacy-data storage/legacy_insurance.csv
   ```

3. **Verify**: Chạy integrity check

---

## 📊 Monitoring Setup

### 1. Application Monitoring

**New Relic / Datadog Configuration**:

```php
// config/monitoring.php
return [
    'insurance' => [
        'enabled' => env('INSURANCE_MONITORING', true),
        
        'thresholds' => [
            'change_detection' => 5000, // ms
            'snapshot_creation' => 10000, // ms
            'excel_export' => 15000, // ms
            'query_time' => 100, // ms
        ],
        
        'alerts' => [
            'slow_operation' => 'insurance-slow-operation',
            'integrity_issue' => 'insurance-integrity-issue',
            'export_failure' => 'insurance-export-failure',
        ],
    ],
];
```

**Middleware cho tracking**:

```php
// app/Http/Middleware/MonitorInsuranceOperations.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class MonitorInsuranceOperations
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);
        
        $response = $next($request);
        
        $duration = (microtime(true) - $start) * 1000; // ms
        
        // Log slow requests
        if ($duration > config('monitoring.insurance.thresholds.query_time')) {
            Log::warning('Slow insurance operation detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration_ms' => $duration,
                'user_id' => auth()->id(),
            ]);
        }
        
        return $response;
    }
}
```

### 2. Database Monitoring

```sql
-- Slow Query Log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1; -- Queries > 1 second

-- Monitor table sizes
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
FROM information_schema.TABLES
WHERE table_schema = 'honghahrm'
    AND table_name LIKE 'insurance_%'
ORDER BY size_mb DESC;

-- Monitor index usage
SELECT 
    table_name,
    index_name,
    seq_in_index,
    column_name
FROM information_schema.STATISTICS
WHERE table_schema = 'honghahrm'
    AND table_name LIKE 'insurance_%'
ORDER BY table_name, index_name, seq_in_index;
```

### 3. Scheduled Monitoring Tasks

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Daily integrity check
    $schedule->command('insurance:check-integrity --fix')
             ->dailyAt('02:00')
             ->emailOutputOnFailure('admin@company.com');
    
    // Weekly performance benchmark
    $schedule->command('insurance:benchmark --export')
             ->weekly()
             ->mondays()
             ->at('03:00')
             ->then(function () {
                 // Analyze and alert if degraded
             });
    
    // Daily backup
    $schedule->command('backup:run --only-db')
             ->dailyAt('01:00');
}
```

### 4. Alert Rules

**Slack/Email Alerts**:

```php
// app/Services/InsuranceAlertService.php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

class InsuranceAlertService
{
    public function alertSlowOperation(string $operation, float $duration): void
    {
        $threshold = config("monitoring.insurance.thresholds.{$operation}");
        
        if ($duration > $threshold) {
            // Slack notification
            Http::post(config('services.slack.webhook'), [
                'text' => "⚠️ Slow Insurance Operation",
                'attachments' => [[
                    'color' => 'warning',
                    'fields' => [
                        ['title' => 'Operation', 'value' => $operation, 'short' => true],
                        ['title' => 'Duration', 'value' => "{$duration}ms", 'short' => true],
                        ['title' => 'Threshold', 'value' => "{$threshold}ms", 'short' => true],
                    ],
                ]],
            ]);
            
            Log::warning("Slow insurance operation: {$operation}", [
                'duration_ms' => $duration,
                'threshold_ms' => $threshold,
            ]);
        }
    }
    
    public function alertIntegrityIssue(int $issueCount): void
    {
        if ($issueCount > 0) {
            Mail::to('admin@company.com')->send(
                new IntegrityIssueAlert($issueCount)
            );
        }
    }
}
```

---

## 🔙 Rollback Procedure

### Khi Nào Rollback?

**Rollback NGAY LẬP TỨC nếu**:
- ❌ Migrations fail
- ❌ Critical errors trong logs (> 10 errors/minute)
- ❌ Database corruption detected
- ❌ Application không accessible
- ❌ Performance degradation nghiêm trọng (> 50% slower)

**Có thể hotfix nếu**:
- ⚠️ Minor UI bugs
- ⚠️ Data inconsistency nhỏ (có thể fix bằng integrity check)
- ⚠️ Slow queries (có thể optimize)

### Rollback Steps

#### 1. Announce Rollback

```bash
# Slack/Email
"🚨 ROLLBACK IN PROGRESS - Insurance module deployment
Reason: [REASON]
ETA: 15-30 minutes
Actions: Reverting to previous version"
```

#### 2. Enable Maintenance Mode

```bash
php artisan down --message="Đang rollback. Vui lòng chờ 15 phút." --retry=60
```

#### 3. Restore Database

```bash
# Stop application from writing to DB
sudo systemctl stop php8.1-fpm

# Restore backup
mysql -u user -p database < backup_pre_insurance_YYYYMMDD_HHMMSS.sql

# Verify restoration
mysql -u user -p -e "SHOW TABLES LIKE 'insurance_%';"
# Should return empty or old tables

# Start application
sudo systemctl start php8.1-fpm
```

#### 4. Revert Code

```bash
# Checkout previous version
git checkout v0.9.9-pre-insurance

# Or restore from backup
rm -rf /var/www/honghahrm/*
cp -r ../honghahrm_backup_YYYYMMDD_HHMMSS/* /var/www/honghahrm/

# Reinstall dependencies
composer install --no-dev
npm ci --production

# Rebuild assets
npm run build
```

#### 5. Clear Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
```

#### 6. Restart Services

```bash
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx
sudo supervisorctl restart laravel-worker:*
```

#### 7. Verify Rollback

```bash
# Test homepage
curl -I https://honghahrm.com

# Test login
# (manual browser test)

# Check logs
tail -100 storage/logs/laravel.log
```

#### 8. Disable Maintenance Mode

```bash
php artisan up
```

#### 9. Post-Rollback Communication

```bash
# Announce completion
"✅ Rollback completed successfully
- Application restored to previous version
- All data intact
- Insurance module deployment postponed
- Root cause analysis in progress"
```

### Rollback Verification Checklist

- [ ] Database restored to pre-deployment state
- [ ] Code reverted to previous version
- [ ] Application accessible
- [ ] No errors in logs
- [ ] Core features working (Login, Employee management, etc.)
- [ ] Users notified
- [ ] Post-mortem scheduled

---

## 📅 Post-Deployment Tasks

### Immediate (Day 1)

- [ ] **Monitor logs** intensively trong 4 giờ đầu
- [ ] **Check error rates**: Phải < 0.1%
- [ ] **Verify performance**: Response time < 500ms
- [ ] **Test all critical paths**:
  - [ ] Tạo hợp đồng có BHXH
  - [ ] Tạo báo cáo tháng
  - [ ] Hoàn tất báo cáo
  - [ ] Xuất Excel
- [ ] **User support**: Monitor #hrm-support channel
- [ ] **Quick wins**: Fix any minor UI bugs immediately

### Week 1

- [ ] **Daily monitoring review**: Check dashboards mỗi sáng
- [ ] **Collect user feedback**: Survey HR team
- [ ] **Performance analysis**: Review benchmark results
- [ ] **Integrity check**: Review daily reports
- [ ] **Training sessions**: Schedule với HR team
- [ ] **Documentation updates**: Fix any inaccuracies found
- [ ] **Bug fixes**: Deploy hotfixes if needed

### Week 2-4

- [ ] **Usage analytics**: Track adoption rate
- [ ] **Performance tuning**: Optimize slow queries
- [ ] **Feature requests**: Collect and prioritize
- [ ] **Advanced training**: Power user sessions
- [ ] **Process improvements**: Refine workflows based on feedback
- [ ] **Retrospective**: Team review meeting

### Month 1

- [ ] **Full system review**: Comprehensive health check
- [ ] **Archive old data**: Clean up test/staging data
- [ ] **Update monitoring thresholds**: Based on real usage
- [ ] **Celebrate success**: Team lunch/recognition 🎉

---

## 📞 Support & Escalation

### Support Tiers

**Tier 1: Slack #hrm-support** (Response: < 1 hour)
- UI questions
- How-to questions
- Minor bugs

**Tier 2: Email support@company.com** (Response: < 4 hours)
- Data issues
- Performance problems
- Feature requests

**Tier 3: On-call Engineer** (+84 xxx xxx xxx)
- Critical production issues
- System down
- Data loss

### Escalation Criteria

**Severity 1 (P0)** - Immediate escalation:
- Application down
- Data loss/corruption
- Security breach
- Complete feature failure affecting all users

**Severity 2 (P1)** - 1 hour escalation:
- Partial feature failure
- Significant performance degradation
- Data inconsistency affecting reports

**Severity 3 (P2)** - 4 hour escalation:
- Minor feature bugs
- Slow queries
- UI glitches

**Severity 4 (P3)** - Next business day:
- Feature requests
- Documentation updates
- Nice-to-have improvements

---

## 🎓 Training Plan

### Training Session 1: Basic Usage (1 hour)
**Audience**: All HR Employees
**Content**:
- Module overview
- Tạo hợp đồng có BHXH
- Xem báo cáo
- Q&A

### Training Session 2: Advanced Features (1.5 hours)
**Audience**: Payroll Admins
**Content**:
- Tạo báo cáo tháng
- Điều chỉnh tháng kê khai
- Hoàn tất và xuất Excel
- Troubleshooting
- Q&A

### Training Session 3: Admin Functions (1 hour)
**Audience**: System Admins
**Content**:
- Cấu hình tỷ lệ BHXH
- Phân quyền
- Chạy integrity check
- Performance monitoring
- Q&A

---

## ✅ Deployment Success Criteria

### Must-Have (Go/No-Go)

- ✅ Migrations completed successfully
- ✅ No critical errors in logs
- ✅ All smoke tests passed
- ✅ Performance within acceptable range
- ✅ Data integrity verified
- ✅ Rollback plan tested

### Nice-to-Have

- ✅ User training completed
- ✅ Documentation distributed
- ✅ Monitoring alerts configured
- ✅ Support team briefed
- ✅ Feedback mechanism in place

### KPIs to Track (First Month)

| Metric | Target | How to Measure |
|--------|--------|----------------|
| Adoption Rate | > 90% | % users created at least 1 report |
| Error Rate | < 0.1% | Errors per request |
| Page Load Time | < 500ms | Average response time |
| Report Generation Time | < 5s | Average time for 100 employees |
| User Satisfaction | > 4/5 stars | Survey results |
| Support Tickets | < 10/week | Ticket system |

---

## 📝 Deployment Checklist Summary

```
PRE-DEPLOYMENT
├─ [ ] Code ready & tested
├─ [ ] Database backed up
├─ [ ] Staging verified
├─ [ ] Documentation ready
├─ [ ] Team briefed
└─ [ ] Rollback plan confirmed

DEPLOYMENT
├─ [ ] Maintenance mode (if needed)
├─ [ ] Pull code
├─ [ ] Install dependencies
├─ [ ] Run migrations
├─ [ ] Seed data
├─ [ ] Backfill participations (if needed)
├─ [ ] Verify integrity
├─ [ ] Build assets
├─ [ ] Update permissions
├─ [ ] Clear caches
├─ [ ] Restart services
├─ [ ] Disable maintenance
├─ [ ] Smoke tests
├─ [ ] Performance verification
└─ [ ] Monitor initial traffic

POST-DEPLOYMENT
├─ [ ] Monitor logs (4 hours)
├─ [ ] User support ready
├─ [ ] Training scheduled
├─ [ ] Feedback collection
├─ [ ] Performance review
└─ [ ] Celebrate! 🎉
```

---

**Document Version**: 1.0  
**Deployment Date**: [TBD]  
**Deployed By**: [Name]  
**Signed Off By**: [Name]

---

**🚀 Ready to deploy? Let's go!**
