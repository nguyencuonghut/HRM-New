# Insurance Data Integrity Check - User Guide

## Overview
The `insurance:check-integrity` command helps maintain data quality in the insurance module by detecting and optionally fixing common data integrity issues.

## Command Signature
```bash
php artisan insurance:check-integrity [--fix] [--detailed]
```

## Options
- `--fix`: Automatically fix issues where possible (without confirmation)
- `--detailed`: Show detailed information for each issue found

## Checks Performed

### 1. Orphaned Participations
**Issue**: Active insurance participations that belong to employees with no active contract.

**Detection**: Queries `insurance_participations` with status ACTIVE where the employee has no APPROVED contracts.

**Auto-fix**: ✅ Yes - Sets participation status to TERMINATED

**Example Output**:
```
⚠ [FIXABLE] Orphaned Participation: Participation #abc-123 for Nguyễn Văn A (ID: 123) has no active contract
```

---

### 2. Missing Components
**Issue**: Active participations that have no component records.

**Detection**: Queries `insurance_participations` with status ACTIVE that have no related `insurance_participation_components`.

**Auto-fix**: ❌ No - Requires manual action (add components or terminate)

**Manual Action**: Either add components via the contract form or set participation status to TERMINATED.

**Example Output**:
```
✗ [MANUAL] Missing Components: Participation #abc-123 for Nguyễn Văn A has no components
```

---

### 3. Invalid Rate Totals
**Issue**: Component rate_total doesn't match sum of employee + employer rates.

**Detection**: Checks if `rate_total` differs from `default_rate_employee + default_rate_employer` by more than 0.0001 (allows floating point tolerance).

**Auto-fix**: ✅ Yes - Recalculates and updates rate_total

**Example Output**:
```
⚠ [FIXABLE] Invalid Rate Total: Component #456 has incorrect rate_total: 0.25 (expected: 0.22)
```

---

### 4. Inactive Component References
**Issue**: Enabled participation components that reference deactivated insurance components.

**Detection**: Checks `insurance_participation_components` with `is_enabled = true` where the related `insurance_component.is_active = false`.

**Auto-fix**: ❌ No - Requires manual review

**Manual Action**: Decide whether to:
- Re-activate the component (if it's still needed)
- Disable the component in the participation (if employee should no longer contribute)

**Example Output**:
```
✗ [MANUAL] Inactive Component Reference: Participation #123 (Nguyễn Văn A) references inactive component BHXH_HUU_TU
```

---

### 5. Duplicate Active Participations
**Issue**: Same employee has multiple active participations.

**Detection**: Groups `insurance_participations` by employee_id where status = ACTIVE, having count > 1.

**Auto-fix**: ❌ No - Requires manual review

**Manual Action**: Review all participations for the employee, keep the most recent one, and set older ones to TERMINATED.

**Example Output**:
```
✗ [MANUAL] Duplicate Participations: Employee Nguyễn Văn A has 2 active participations
   Details: IDs: abc-123, def-456
```

---

### 6. Missing Insurance Salary
**Issue**: Active participations with NULL or zero insurance_salary.

**Detection**: Queries `insurance_participations` with status ACTIVE where `insurance_salary IS NULL OR insurance_salary <= 0`.

**Auto-fix**: ❌ No - Requires manual action

**Manual Action**: Set the insurance_salary field from the contract's insurance_salary value.

**Example Output**:
```
✗ [MANUAL] Missing Insurance Salary: Participation #abc-123 for Nguyễn Văn A has no insurance_salary
   Details: Current value: NULL
```

---

## Usage Examples

### Basic Check (Read-only)
```bash
php artisan insurance:check-integrity
```
**Output**: Lists all issues found with type (FIXABLE or MANUAL)

---

### Auto-fix Issues
```bash
php artisan insurance:check-integrity --fix
```
**Output**: Fixes all auto-fixable issues and shows confirmation messages

---

### Detailed Report
```bash
php artisan insurance:check-integrity --detailed
```
**Output**: Shows additional details for each issue (IDs, dates, etc.)

---

### Fix + Detailed
```bash
php artisan insurance:check-integrity --fix --detailed
```
**Output**: Fixes issues and shows detailed information

---

## Exit Codes
- `0`: Success - No issues found (data is healthy)
- `1`: Failure - Issues found (requires attention)

---

## Recommended Schedule

### Daily (Automated via Cron)
```bash
# Check only, send report via email if issues found
0 1 * * * cd /path/to/project && php artisan insurance:check-integrity | mail -s "Insurance Integrity Report" admin@example.com
```

### Weekly (Manual Review)
```bash
# Run with --fix to auto-clean orphaned data
php artisan insurance:check-integrity --fix
```

### Before Production Deployment
```bash
# Run to ensure data is clean before going live
php artisan insurance:check-integrity --detailed
```

---

## Integration with Monitoring

### Example Shell Script (check_and_alert.sh)
```bash
#!/bin/bash

OUTPUT=$(php artisan insurance:check-integrity 2>&1)
EXIT_CODE=$?

if [ $EXIT_CODE -ne 0 ]; then
    echo "⚠️ Insurance integrity issues detected!"
    echo "$OUTPUT"
    # Send to Slack, email, or monitoring system
    curl -X POST https://hooks.slack.com/... -d "{\"text\":\"$OUTPUT\"}"
fi

exit $EXIT_CODE
```

---

## Performance Considerations

### Execution Time
- Small database (< 1000 employees): ~0.01-0.05s
- Medium database (1000-10000 employees): ~0.1-0.5s
- Large database (> 10000 employees): ~0.5-2s

### Database Load
- Read-only operations (without --fix): Minimal impact
- With --fix: Light write operations (only updates affected records)

### Recommendation
- Safe to run during business hours
- Can be scheduled in off-peak hours for large databases

---

## Troubleshooting

### Issue: Command takes too long
**Solution**: Check if indexes exist on:
- `insurance_participations.status`
- `insurance_participations.employee_id`
- `insurance_participation_components.is_enabled`
- `insurance_components.is_active`

```sql
CREATE INDEX idx_participations_status ON insurance_participations(status);
CREATE INDEX idx_participations_employee ON insurance_participations(employee_id);
```

---

### Issue: "MANUAL" issues keep appearing
**Solution**: These require human review:
1. Review the details output
2. Use the suggested manual actions
3. Update records via admin interface or SQL

---

### Issue: Fixed issues reappear
**Solution**: Investigate root cause:
- Check ContractObserver logic
- Verify InsuranceParticipation creation workflow
- Review recent data imports

---

## Best Practices

1. **Run before monthly report generation**
   ```bash
   php artisan insurance:check-integrity --fix
   php artisan insurance:generate-monthly-report
   ```

2. **Include in deployment checklist**
   - Run in staging environment first
   - Verify no critical MANUAL issues
   - Run with --fix in production

3. **Monitor trends**
   - Track number of issues over time
   - If orphaned participations increase → check contract workflow
   - If missing components increase → check contract form logic

4. **Document manual fixes**
   - Keep log of MANUAL issues resolved
   - Identify patterns to prevent future issues

---

## Related Commands

```bash
# Backfill insurance profiles from legacy contracts
php artisan insurance:backfill-profiles

# Backfill component data from old boolean fields
php artisan insurance:backfill-components

# Suggest insurance grade raises
php artisan insurance:suggest-grade-raise

# Expire old suggestions
php artisan insurance:expire-suggestions
```

---

## FAQ

**Q: Is it safe to use --fix in production?**  
A: Yes, auto-fix only handles non-destructive operations (setting TERMINATED status, recalculating totals). It never deletes data.

**Q: What if I accidentally run --fix?**  
A: Terminated participations can be reactivated by updating the status field. Recalculated rates can be manually overridden if needed.

**Q: How often should I run this command?**  
A: Recommended schedule:
- Daily: Read-only check (monitoring)
- Weekly: With --fix (maintenance)
- Before/after major data imports: With --fix --detailed

**Q: Can I run this on a replica database?**  
A: Yes for read-only mode. Avoid --fix on replicas as it writes data.

**Q: What's the difference between this and migrate:status?**  
A: This checks data integrity within existing tables, not schema migrations. Both are important for different reasons.

---

## Example Output Interpretation

```
===========================================
Insurance Data Integrity Check
===========================================

1. Checking for orphaned participations...
   ✓ No orphaned participations                    ← All good
2. Checking for participations without components...
   Found 3 participations without components       ← Needs attention
3. Checking for invalid rate_total values...
   ✓ All rate_total values are correct             ← All good
4. Checking for inactive component references...
   ✓ No inactive component references              ← All good
5. Checking for duplicate active participations...
   Found 1 employees with duplicate participations ← Needs attention
6. Checking for missing insurance_salary...
   ✓ All participations have insurance_salary      ← All good

===========================================
Summary
===========================================
Execution time: 0.03s
Total issues found: 4                              ← 4 issues need attention
Fixable issues: 0                                  ← All require manual review

Issues Details:
✗ [MANUAL] Missing Components: ...                 ← Manual action needed
✗ [MANUAL] Missing Components: ...
✗ [MANUAL] Missing Components: ...
✗ [MANUAL] Duplicate Participations: ...
```

**Interpretation**:
- 2 checks passed (✓)
- 2 checks failed with 4 total issues
- All issues require manual review (no auto-fix available)
- Action: Review the employee records and fix via admin interface

---

## Version History

### v1.0.0 (Phase 5.1)
- Initial release
- 6 integrity checks
- Auto-fix support for 2 check types
- Detailed reporting option
- Exit code support for CI/CD integration
