# Insurance Module - Production Deployment Runbook

## 🎯 Quick Reference

**Deployment Date**: _______________
**Environment**: Production
**Deployment Lead**: _______________
**Start Time**: _______________
**End Time**: _______________

---

## ⏱️ Timeline (Estimated: 2-4 hours)

| Time | Task | Duration | Owner | Status |
|------|------|----------|-------|--------|
| T+0 | Pre-deployment checks | 30 min | DevOps | ☐ |
| T+30 | Enable maintenance mode | 2 min | DevOps | ☐ |
| T+32 | Backup database | 10 min | DBA | ☐ |
| T+42 | Deploy code | 5 min | DevOps | ☐ |
| T+47 | Run migrations | 5 min | DevOps | ☐ |
| T+52 | Seed initial data | 2 min | DevOps | ☐ |
| T+54 | Backfill participations | 15 min | DevOps | ☐ |
| T+69 | Verify integrity | 5 min | DevOps | ☐ |
| T+74 | Build assets | 5 min | DevOps | ☐ |
| T+79 | Update permissions | 5 min | DevOps | ☐ |
| T+84 | Clear caches | 2 min | DevOps | ☐ |
| T+86 | Restart services | 3 min | DevOps | ☐ |
| T+89 | Disable maintenance | 1 min | DevOps | ☐ |
| T+90 | Smoke tests | 15 min | QA | ☐ |
| T+105 | Performance verification | 10 min | DevOps | ☐ |
| T+115 | Monitor initial traffic | 30 min | All | ☐ |

---

## 📋 Detailed Checklist

### PRE-DEPLOYMENT (T-60 to T+0)

#### 60 Minutes Before

- [ ] **Team assembled on Slack/Teams**
  - DevOps Lead: _______________
  - Backend Dev: _______________
  - DBA: _______________
  - QA Tester: _______________

- [ ] **Code verification**
  ```bash
  # Verify tag exists
  git tag | grep v1.0.0-insurance-module
  
  # Verify no uncommitted changes
  git status
  ```

- [ ] **Staging final check**
  - [ ] All UAT tests passed
  - [ ] No critical bugs
  - [ ] Performance acceptable
  - Sign-off: _______________

- [ ] **Communication sent**
  - [ ] Users notified (Email/Slack)
  - [ ] Maintenance window announced
  - [ ] Support team on standby

#### 30 Minutes Before

- [ ] **Server health check**
  ```bash
  # Disk space (need > 500MB free)
  df -h
  
  # CPU & RAM
  htop
  
  # MySQL status
  sudo systemctl status mysql
  
  # PHP-FPM status
  sudo systemctl status php8.1-fpm
  
  # Nginx status
  sudo systemctl status nginx
  ```

- [ ] **Backup verification**
  ```bash
  # Check backup script exists
  ls -lh /path/to/backup/script.sh
  
  # Check backup storage available
  df -h /backups/
  ```

- [ ] **Rollback plan reviewed**
  - [ ] Rollback procedure documented
  - [ ] Backup restoration tested on staging
  - [ ] Previous version code tagged

---

### DEPLOYMENT (T+0 to T+90)

#### Step 1: Enable Maintenance Mode (T+0)

```bash
# SSH to production
ssh user@production-server

# Navigate to project
cd /var/www/honghahrm

# Enable maintenance
php artisan down --message="Đang nâng cấp Module BHXH. Dự kiến 90 phút." --retry=60

# Verify maintenance page shows
curl -I https://honghahrm.com
# Expected: HTTP 503
```

**Completed at**: _______________
**Verified by**: _______________

#### Step 2: Backup Database (T+2)

```bash
# Create backup directory if not exists
sudo mkdir -p /backups/honghahrm/

# Backup full database
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
mysqldump -u dbuser -p'PASSWORD' honghahrm_prod > /backups/honghahrm/backup_pre_insurance_${TIMESTAMP}.sql

# Verify backup file
ls -lh /backups/honghahrm/backup_pre_insurance_${TIMESTAMP}.sql

# Test backup can be read
head -20 /backups/honghahrm/backup_pre_insurance_${TIMESTAMP}.sql

# Record backup filename
echo "Backup file: /backups/honghahrm/backup_pre_insurance_${TIMESTAMP}.sql" >> deployment.log
```

**Backup filename**: _______________
**Backup size**: _______________
**Completed at**: _______________
**Verified by**: _______________

#### Step 3: Deploy Code (T+12)

```bash
# Backup current code
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
sudo cp -r /var/www/honghahrm /var/www/honghahrm_backup_${TIMESTAMP}

# Pull latest code
cd /var/www/honghahrm
git fetch origin
git checkout v1.0.0-insurance-module

# Verify version
git describe --tags
# Expected: v1.0.0-insurance-module

# Install Composer dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# Install NPM dependencies
npm ci --production
```

**Code version deployed**: _______________
**Completed at**: _______________
**Verified by**: _______________

#### Step 4: Run Migrations (T+17)

```bash
# Dry-run first (show SQL without executing)
php artisan migrate --pretend

# Review output carefully
# If OK, run actual migration
php artisan migrate --force

# Expected output:
# Migration table created successfully.
# Migrating: 2026_01_12_000000_create_insurance_tables
# Migrated:  2026_01_12_000000_create_insurance_tables (2.34s)
# Migrating: 2026_01_12_100000_add_insurance_performance_indexes
# Migrated:  2026_01_12_100000_add_insurance_performance_indexes (0.45s)

# Verify tables created
php artisan tinker
>>> DB::select("SHOW TABLES LIKE 'insurance_%'");
>>> exit
```

**Migrations completed at**: _______________
**Any errors?**: ☐ No  ☐ Yes (describe): _______________
**Verified by**: _______________

#### Step 5: Seed Initial Data (T+22)

```bash
# Seed insurance components
php artisan db:seed --class=InsuranceComponentSeeder

# Verify seeded data
php artisan tinker
>>> \App\Models\InsuranceComponent::count();
=> 5
>>> \App\Models\InsuranceComponent::pluck('name');
=> [
     "BHXH - Hưu trí",
     "BHXH - Ốm đau",
     "BHXH - TNLĐ-BNN",
     "BHTN (Bảo hiểm thất nghiệp)",
     "BHYT (Bảo hiểm y tế)",
   ]
>>> exit
```

**Seeding completed at**: _______________
**Components count**: _______________
**Verified by**: _______________

#### Step 6: Backfill Participations (T+24)

```bash
# Dry-run first to see what will be created
php artisan insurance:backfill-participations --dry-run

# Review output
# If OK, run actual backfill
php artisan insurance:backfill-participations

# Confirm when prompted
# Monitor progress bar

# Record results
```

**Contracts backfilled**: _______________
**Any errors?**: ☐ No  ☐ Yes (count): _______________
**Completed at**: _______________
**Verified by**: _______________

#### Step 7: Verify Data Integrity (T+39)

```bash
# Run integrity check
php artisan insurance:check-integrity --detailed

# Expected: 0 issues
# If issues found, review and fix before continuing
```

**Issues found**: _______________
**All fixed?**: ☐ Yes  ☐ No (stop deployment!)
**Completed at**: _______________
**Verified by**: _______________

#### Step 8: Build Frontend Assets (T+44)

```bash
# Build for production
npm run build

# Verify build
ls -lh public/build/manifest.json
ls -lh public/build/assets/

# Expected: Multiple .js and .css files with hashes
```

**Build completed at**: _______________
**Build size**: _______________
**Verified by**: _______________

#### Step 9: Update Permissions (T+49)

```bash
php artisan tinker

# Assign to Payroll Admin role
$role = \Spatie\Permission\Models\Role::findByName('payroll_admin');
$role->givePermissionTo([
  'view_insurance_reports',
  'create_insurance_reports',
  'update_insurance_reports',
  'finalize_insurance_reports',
  'export_insurance_reports',
  'manage_insurance_components',
]);

# Assign to HR Employee role
$hrRole = \Spatie\Permission\Models\Role::findByName('hr_employee');
$hrRole->givePermissionTo([
  'view_insurance_reports',
  'view_insurance_details',
]);

# Verify
$role->permissions->pluck('name');
$hrRole->permissions->pluck('name');

exit
```

**Permissions updated at**: _______________
**Verified by**: _______________

#### Step 10: Clear & Warm Caches (T+54)

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

**Completed at**: _______________
**Verified by**: _______________

#### Step 11: Restart Services (T+56)

```bash
# Restart PHP-FPM
sudo systemctl restart php8.1-fpm
sudo systemctl status php8.1-fpm

# Restart queue workers (if using)
sudo supervisorctl restart laravel-worker:*
sudo supervisorctl status

# Restart Nginx
sudo systemctl restart nginx
sudo systemctl status nginx
```

**All services restarted**: ☐ Yes  ☐ No (describe issue): _______________
**Completed at**: _______________
**Verified by**: _______________

#### Step 12: Disable Maintenance Mode (T+59)

```bash
# Disable maintenance
php artisan up

# Verify site accessible
curl -I https://honghahrm.com
# Expected: HTTP 200
```

**Site live at**: _______________
**Verified by**: _______________

---

### POST-DEPLOYMENT VERIFICATION (T+60 to T+120)

#### Step 13: Smoke Tests (T+60)

**Test 1: Homepage**
```bash
curl -I https://honghahrm.com
# Expected: HTTP 200
```
**Result**: ☐ Pass  ☐ Fail

**Test 2: Login**
- Open browser: https://honghahrm.com/login
- Login with test account
- **Result**: ☐ Pass  ☐ Fail

**Test 3: Insurance Menu Visible**
- Navigate to main menu
- Verify "Quản lý BHXH" menu item exists
- **Result**: ☐ Pass  ☐ Fail

**Test 4: View Insurance Components (Admin)**
- Go to: Quản lý BHXH → Cấu hình thành phần
- Should see 5 components
- **Result**: ☐ Pass  ☐ Fail

**Test 5: Create Contract with Insurance**
- Go to: Hợp đồng → Tạo mới
- Fill form with insurance info
- Save
- **Result**: ☐ Pass  ☐ Fail

**Test 6: Create Insurance Report**
- Go to: Quản lý BHXH → Báo cáo BHXH
- Create new report for current month
- **Result**: ☐ Pass  ☐ Fail

**All smoke tests passed?**: ☐ Yes  ☐ No (stop and investigate!)
**Tested by**: _______________

#### Step 14: Performance Verification (T+75)

```bash
# Run benchmark
php artisan insurance:benchmark --employees=100

# Review results
# All operations should be EXCELLENT or GOOD
```

**Performance results**:
- Change Detection: _____ ms [___________]
- Snapshot Creation: _____ ms [___________]
- Excel Export: _____ ms [___________]
- Query Performance: _____ ms [___________]

**Overall**: ☐ Acceptable  ☐ Needs investigation
**Verified by**: _______________

#### Step 15: Monitor Initial Traffic (T+85)

```bash
# Tail logs for 30 minutes
tail -f storage/logs/laravel.log

# Watch for:
# - Error rates
# - Slow queries
# - Unexpected warnings
```

**Monitoring period**: _____ to _____
**Error count**: _____
**Any critical issues?**: ☐ No  ☐ Yes (describe): _______________
**Monitored by**: _______________

---

## 🚨 ROLLBACK PROCEDURE

**TRIGGER ROLLBACK IF**:
- ❌ Migrations fail
- ❌ Critical errors > 10/minute
- ❌ Database corruption detected
- ❌ Application not accessible after 10 minutes
- ❌ Performance degradation > 50%

**Rollback decision made by**: _______________
**Rollback started at**: _______________

### Rollback Steps

1. **Announce**
   ```
   🚨 ROLLBACK IN PROGRESS
   Reason: _______________
   ETA: 30 minutes
   ```

2. **Enable Maintenance**
   ```bash
   php artisan down --message="Đang rollback. Vui lòng chờ 30 phút."
   ```

3. **Stop Services**
   ```bash
   sudo systemctl stop php8.1-fpm
   ```

4. **Restore Database**
   ```bash
   mysql -u dbuser -p honghahrm_prod < /backups/honghahrm/backup_pre_insurance_TIMESTAMP.sql
   ```

5. **Revert Code**
   ```bash
   git checkout v0.9.9-pre-insurance
   composer install --no-dev
   npm ci --production
   npm run build
   ```

6. **Clear Caches**
   ```bash
   php artisan cache:clear
   php artisan config:cache
   ```

7. **Restart Services**
   ```bash
   sudo systemctl start php8.1-fpm
   sudo systemctl restart nginx
   ```

8. **Disable Maintenance**
   ```bash
   php artisan up
   ```

9. **Verify Rollback**
   ```bash
   curl -I https://honghahrm.com
   # Test login
   ```

**Rollback completed at**: _______________
**Rollback verified by**: _______________

---

## ✅ SIGN-OFF

### Deployment Sign-Off

- [ ] All migrations completed successfully
- [ ] All smoke tests passed
- [ ] Performance acceptable
- [ ] No critical errors in logs
- [ ] Data integrity verified
- [ ] Users can access the system

**Deployment Status**: ☐ Success  ☐ Partial  ☐ Failed  ☐ Rolled Back

**Signed by**:
- DevOps Lead: _______________ Date/Time: _______________
- Backend Dev: _______________ Date/Time: _______________
- QA Tester: _______________ Date/Time: _______________
- Business Owner: _______________ Date/Time: _______________

### Post-Deployment Tasks

- [ ] Send deployment completion email
- [ ] Update deployment documentation
- [ ] Schedule training sessions
- [ ] Plan monitoring review meetings
- [ ] Create post-mortem (if issues occurred)

---

## 📞 Emergency Contacts

**DevOps Lead**: _______________ | Phone: _______________
**Backend Dev**: _______________ | Phone: _______________
**DBA**: _______________ | Phone: _______________
**CTO/Manager**: _______________ | Phone: _______________

**On-Call Hotline**: +84 ___ ___ ___

---

## 📝 Notes & Issues

| Time | Issue | Resolution | Resolved By |
|------|-------|------------|-------------|
|      |       |            |             |
|      |       |            |             |
|      |       |            |             |

---

**Runbook Version**: 1.0  
**Last Updated**: January 12, 2026  
**Next Review**: After deployment completion
