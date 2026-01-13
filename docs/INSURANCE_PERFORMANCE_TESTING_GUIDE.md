# Insurance Module Performance Testing Guide

## Overview
This guide covers performance testing, benchmarking, and optimization for the insurance module at scale (100-10,000+ employees).

---

## Benchmark Command

### Basic Usage
```bash
php artisan insurance:benchmark
```

### Options
```bash
# Specify employee count for simulation
php artisan insurance:benchmark --employees=1000

# Run multiple iterations for accuracy
php artisan insurance:benchmark --iterations=5

# Include Excel export in benchmark
php artisan insurance:benchmark --export

# Full test with all options
php artisan insurance:benchmark --employees=5000 --iterations=5 --export
```

---

## Benchmark Operations

### 1. Change Detection (Report Generation)
**Operation**: Detect insurance changes for monthly report  
**Measures**: Query time to identify increases, decreases, adjustments

**Performance Targets**:
- **Small scale** (< 100 employees): < 100ms
- **Medium scale** (100-1,000 employees): < 500ms  
- **Large scale** (1,000-10,000 employees): < 2,000ms
- **Extra large** (> 10,000 employees): < 5,000ms

**Status Indicators**:
- 🟢 **EXCELLENT**: < 500ms
- 🟡 **GOOD**: 500ms - 2,000ms
- 🔴 **NEEDS OPTIMIZATION**: > 2,000ms

---

### 2. Snapshot Creation (Finalization)
**Operation**: Create immutable snapshot of contributions for report  
**Measures**: Time to calculate and insert all contribution records

**Performance Targets**:
- **Small scale**: < 1,000ms
- **Medium scale**: < 5,000ms
- **Large scale**: < 15,000ms
- **Extra large**: < 30,000ms

**Status Indicators**:
- 🟢 **EXCELLENT**: < 5,000ms
- 🟡 **GOOD**: 5,000ms - 15,000ms
- 🔴 **NEEDS OPTIMIZATION**: > 15,000ms

---

### 3. Excel Export
**Operation**: Generate downloadable Excel report  
**Measures**: Time to create and write Excel file

**Performance Targets**:
- **Small file** (< 100 rows): < 1,000ms
- **Medium file** (100-1,000 rows): < 3,000ms
- **Large file** (1,000-5,000 rows): < 10,000ms
- **Extra large** (> 5,000 rows): < 30,000ms

**Status Indicators**:
- 🟢 **EXCELLENT**: < 3,000ms
- 🟡 **GOOD**: 3,000ms - 10,000ms
- 🔴 **NEEDS OPTIMIZATION**: > 10,000ms

---

### 4. Database Queries
**Operation**: Common query patterns used throughout module  
**Measures**: Individual query execution time

**Performance Targets**:
- **Simple queries** (single table): < 10ms
- **Join queries** (2-3 tables): < 50ms
- **Complex aggregations**: < 200ms

**Status Indicators**:
- 🟢 **EXCELLENT**: < 50ms
- 🟡 **GOOD**: 50ms - 200ms
- 🔴 **NEEDS OPTIMIZATION**: > 200ms

---

## Benchmark Results Interpretation

### Example Output
```
===========================================
Benchmark Results Summary
===========================================

Change Detection
  Time: 3.45 ms [EXCELLENT]
  Throughput: 245 records/sec

Snapshot Creation
  Time: 4,234 ms [EXCELLENT]
  Throughput: 78 contributions/sec

Excel Export
  Time: 2,156 ms [EXCELLENT]
  Average file size: 234.5 KB

Database Queries
  Active Participations: 1.06 ms [EXCELLENT]
  Participation with Components: 1.24 ms [EXCELLENT]
  Monthly Reports (Last Year): 0.87 ms [EXCELLENT]
  Recent Change Records: 0.76 ms [EXCELLENT]
```

**Interpretation**:
- ✅ All operations performing excellently
- ✅ No bottlenecks detected
- ✅ System ready for production scale
- ✅ Can handle current employee count efficiently

---

## Performance Optimization Strategies

### 1. Database Indexing

#### Required Indexes
```sql
-- Contracts table
CREATE INDEX idx_contracts_status_start ON contracts(status, start_date);
CREATE INDEX idx_contracts_status_end ON contracts(status, end_date);
CREATE INDEX idx_contracts_employee_status ON contracts(employee_id, status);

-- Insurance Participations
CREATE INDEX idx_participations_status ON insurance_participations(status);
CREATE INDEX idx_participations_employee_status ON insurance_participations(employee_id, status);
CREATE INDEX idx_participations_updated ON insurance_participations(updated_at);

-- Participation Components
CREATE INDEX idx_components_participation ON insurance_participation_components(insurance_participation_id);
CREATE INDEX idx_components_enabled ON insurance_participation_components(is_enabled);

-- Change Records
CREATE INDEX idx_records_report_status ON insurance_change_records(report_id, approval_status);
CREATE INDEX idx_records_created ON insurance_change_records(created_at);

-- Monthly Contributions
CREATE INDEX idx_contributions_report ON insurance_monthly_contributions(report_id);
CREATE INDEX idx_contributions_employee ON insurance_monthly_contributions(employee_id);
```

#### Verify Indexes
```sql
SHOW INDEXES FROM insurance_participations;
```

---

### 2. Query Optimization

#### Use Eager Loading
```php
// ❌ Bad: N+1 query problem
$participations = InsuranceParticipation::all();
foreach ($participations as $p) {
    echo $p->employee->name; // Separate query each time
}

// ✅ Good: Eager load relationships
$participations = InsuranceParticipation::with(['employee', 'components.component'])->get();
```

#### Use Chunking for Large Datasets
```php
// ❌ Bad: Load all at once (memory spike)
$participations = InsuranceParticipation::all();

// ✅ Good: Process in chunks
InsuranceParticipation::chunk(500, function ($participations) {
    foreach ($participations as $participation) {
        // Process...
    }
});
```

#### Use Select Specific Columns
```php
// ❌ Bad: Load all columns
$employees = Employee::all();

// ✅ Good: Only needed columns
$employees = Employee::select('id', 'employee_code', 'full_name')->get();
```

---

### 3. Caching Strategy

#### Cache Active Components
```php
// Cache for 1 hour (rarely changes)
$components = Cache::remember('insurance_components_active', 3600, function () {
    return InsuranceComponent::where('is_active', true)->get();
});
```

#### Cache Report Metadata
```php
// Cache report list for 5 minutes
$reports = Cache::remember("insurance_reports_{$year}", 300, function () use ($year) {
    return InsuranceMonthlyReport::where('year', $year)->get();
});
```

---

### 4. Background Processing

#### Queue Long-Running Operations
```php
// For large reports, use queue
if ($employeeCount > 500) {
    FinalizeInsuranceReportJob::dispatch($report);
    return response()->json(['message' => 'Finalization started in background']);
}
```

#### Job Example
```php
class FinalizeInsuranceReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(InsuranceSnapshotService $service)
    {
        $service->createSnapshot($this->report);
        
        // Notify user when complete
        $this->report->creator->notify(new ReportFinalizedNotification($this->report));
    }
}
```

---

### 5. Memory Management

#### Monitor Memory Usage
```php
$startMemory = memory_get_usage(true);

// ... operation ...

$endMemory = memory_get_usage(true);
$memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // MB

if ($memoryUsed > 100) {
    Log::warning("High memory usage: {$memoryUsed} MB");
}
```

#### Increase Memory Limit (if needed)
```php
// In command handle() method
ini_set('memory_limit', '512M'); // For large operations
```

---

## Load Testing Scenarios

### Scenario 1: Monthly Report Peak Load
**Situation**: End of month, HR generates reports for all departments

```bash
# Simulate 100 concurrent report generations
ab -n 100 -c 10 http://localhost/insurance-reports/generate

# Expected: < 30s total time, no errors
```

---

### Scenario 2: Mass Employee Onboarding
**Situation**: 50 new employees hired in one day

**Test Script**:
```php
// Create 50 contracts with insurance
for ($i = 1; $i <= 50; $i++) {
    $contract = Contract::factory()->create([
        'status' => 'APPROVED',
        'insurance_salary' => 10000000,
    ]);
    
    // Create participation + components
    $participation = InsuranceParticipation::create([...]);
    // Add 5 components
}

// Then run benchmark
php artisan insurance:benchmark
```

**Expected Results**:
- Change detection: < 100ms
- No memory errors
- All participations created successfully

---

### Scenario 3: Year-End Mass Export
**Situation**: Export all 12 monthly reports for auditing

**Test Script**:
```bash
for month in {01..12}; do
    time php artisan insurance:export-report 2025-$month
done
```

**Expected**:
- Each export: < 10s
- Total time: < 2 minutes
- No file corruption

---

## Performance Monitoring

### Key Metrics to Track

#### Application Metrics
- **Report generation time**: Track via log or APM
- **Finalization success rate**: Should be > 99.9%
- **Export time**: Monitor per-report
- **Memory peak usage**: Should not exceed 256MB

#### Database Metrics
- **Query response time**: Average < 50ms
- **Connection pool usage**: Should not max out
- **Lock wait time**: < 1s
- **Slow query log**: No queries > 1s

#### System Metrics
- **CPU usage**: Peak < 80% during operations
- **Memory usage**: Peak < 70% of available
- **Disk I/O**: Monitor for Excel exports
- **Network latency**: If using remote DB

---

### Monitoring Setup

#### Laravel Telescope (Dev/Staging)
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

#### Log Key Operations
```php
Log::info('Insurance report finalized', [
    'report_id' => $report->id,
    'duration' => $duration,
    'contributions' => $contributionCount,
    'memory' => memory_get_peak_usage(true) / 1024 / 1024 . ' MB'
]);
```

#### Create Performance Dashboard
- Track average finalization time
- Alert if > 30s
- Track export success rate
- Monitor integrity check failures

---

## Optimization Checklist

### Before Production Deploy
- [ ] All required indexes created
- [ ] Benchmark run with expected employee count
- [ ] All operations < target thresholds
- [ ] Memory usage < 256MB for typical operations
- [ ] No N+1 query issues (check Telescope)
- [ ] Eager loading implemented where needed
- [ ] Chunking used for large datasets
- [ ] Background jobs configured for large reports
- [ ] Caching enabled for static data
- [ ] Error handling for timeouts

### Monthly Maintenance
- [ ] Run integrity check
- [ ] Archive old reports (> 2 years)
- [ ] Vacuum/optimize database tables
- [ ] Review slow query log
- [ ] Check average operation times
- [ ] Verify index usage statistics

### Quarterly Review
- [ ] Re-run full benchmark suite
- [ ] Review and adjust cache TTLs
- [ ] Analyze query patterns
- [ ] Update performance baselines
- [ ] Test with peak load scenarios

---

## Troubleshooting Performance Issues

### Issue: Slow Change Detection
**Symptoms**: Report generation takes > 5s

**Diagnosis**:
```sql
EXPLAIN SELECT * FROM contracts 
WHERE status = 'APPROVED' 
AND YEAR(start_date) = 2025 
AND MONTH(start_date) = 12;
```

**Solutions**:
1. Add composite index on (status, start_date)
2. Consider materialized view for recent changes
3. Cache results for same month/year

---

### Issue: Finalization Timeout
**Symptoms**: 504 Gateway Timeout during finalization

**Diagnosis**:
- Check number of contributions being created
- Monitor memory usage
- Check for database locks

**Solutions**:
1. Move to background job (queue)
2. Increase PHP timeout temporarily
3. Process in smaller batches
4. Add progress tracking

---

### Issue: Memory Exhausted
**Symptoms**: "Allowed memory size exhausted" error

**Diagnosis**:
```php
Log::info('Memory usage', [
    'current' => memory_get_usage(true) / 1024 / 1024 . ' MB',
    'peak' => memory_get_peak_usage(true) / 1024 / 1024 . ' MB'
]);
```

**Solutions**:
1. Use chunking instead of `all()`
2. Unset variables after use
3. Process in batches with `chunk()`
4. Increase memory_limit temporarily

---

### Issue: Slow Excel Export
**Symptoms**: Export takes > 30s for 1000 rows

**Diagnosis**:
- Check file size
- Profile export code
- Test on smaller dataset

**Solutions**:
1. Use streaming export (Laravel Excel)
2. Simplify cell formatting
3. Remove unnecessary calculations
4. Use queue for large exports

---

## Performance Test Results Template

### Test Environment
- **Date**: 2026-01-12
- **Database**: MySQL 8.0
- **PHP Version**: 8.2
- **Employee Count**: 1,000
- **Report Count**: 12

### Benchmark Results

| Operation | Time (ms) | Status | Notes |
|-----------|-----------|--------|-------|
| Change Detection | 245 | 🟢 EXCELLENT | 0 records detected |
| Snapshot Creation | 4,234 | 🟢 EXCELLENT | 78 contributions/sec |
| Excel Export | 2,156 | 🟢 EXCELLENT | 234 KB file |
| Active Participations Query | 1.06 | 🟢 EXCELLENT | - |
| Participation with Components | 1.24 | 🟢 EXCELLENT | - |

### Conclusions
- ✅ System performs well at current scale
- ✅ No optimization needed immediately
- ✅ Ready for production deployment
- ⚠️ Monitor when employee count reaches 5,000

### Recommendations
1. Set up monitoring alerts for operations > 10s
2. Schedule monthly benchmark runs
3. Archive reports older than 2 years
4. Consider read replica for reporting queries at 5,000+ employees

---

## Performance Best Practices Summary

### ✅ Do's
- Use database indexes on frequently queried columns
- Eager load relationships to avoid N+1 queries
- Chunk large datasets instead of loading all at once
- Cache static/rarely-changing data
- Use background jobs for operations > 30s
- Monitor and log performance metrics
- Test with realistic data volumes
- Profile slow operations

### ❌ Don'ts
- Load all records with `Model::all()` for large tables
- Use loops with individual queries (N+1 problem)
- Ignore slow query warnings
- Skip indexing foreign keys
- Run heavy operations synchronously
- Forget to unset large variables
- Deploy without performance testing
- Over-cache frequently changing data

---

## Additional Resources

### Laravel Performance
- [Laravel Optimization Guide](https://laravel.com/docs/optimization)
- [Database Query Performance](https://laravel.com/docs/database#query-optimization)
- [Queue Workers](https://laravel.com/docs/queues)

### MySQL Optimization
- [MySQL Performance Tuning](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)
- [Index Optimization](https://dev.mysql.com/doc/refman/8.0/en/optimization-indexes.html)

### Monitoring Tools
- Laravel Telescope (development)
- New Relic (production)
- Datadog (production)
- Custom logging + Grafana

---

## Version History

### v1.0.0 (Phase 5.2)
- Initial performance testing framework
- Benchmark command implementation
- Performance targets defined
- Optimization strategies documented
