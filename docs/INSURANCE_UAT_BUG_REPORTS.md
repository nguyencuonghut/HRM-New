# Insurance Module - UAT Bug Reports

## Bug Tracking Summary

**UAT Period**: January 15 - February 5, 2026  
**Total Bugs Reported**: 0  
**Critical**: 0 | **High**: 0 | **Medium**: 0 | **Low**: 0  
**Status**: Open: 0 | Fixed: 0 | Closed: 0 | Won't Fix: 0

---

## Critical Bugs (P0)

### INS-001: [SAMPLE] Report finalization fails with timeout
**Severity**: Critical  
**Priority**: P0  
**Status**: Sample - Delete this after first real bug

**Reported By**: HR Payroll Admin  
**Date**: 2026-01-15  
**Environment**: UAT

**Steps to Reproduce**:
1. Create monthly report with 100+ employees
2. Click "Hoàn tất báo cáo"
3. Wait 30 seconds
4. Page shows 504 timeout error

**Expected Result**:
Report should finalize within 30 seconds and show success message

**Actual Result**:
504 Gateway Timeout error after 30 seconds, report remains in DRAFT status

**Impact**:
- Blocks monthly report processing
- Cannot generate insurance declaration
- Affects all users

**Screenshots**:
[Attach screenshot of error]

**Browser/Device**:
- Chrome 120 on Windows 11
- Also tested on Firefox - same issue

**Additional Notes**:
- Tested with 50 employees: works fine (~5 seconds)
- Issue only occurs with 100+ employees
- Database shows no errors in logs

**Root Cause** (Dev Team):
PHP timeout set to 30s, needs increase or background job

**Fix Description**:
Move finalization to queue job for reports > 100 employees

**Status History**:
- 2026-01-15 10:00: Reported
- 2026-01-15 14:30: Confirmed by Dev
- 2026-01-16 09:00: Fix deployed
- 2026-01-16 11:00: Retested - PASSED
- 2026-01-16 14:00: CLOSED

---

## High Priority Bugs (P1)

### INS-XXX: [Title]
**Severity**: High  
**Priority**: P1  
**Status**: [Open/In Progress/Fixed/Closed]

[Copy format from INS-001 above]

---

## Medium Priority Bugs (P2)

### INS-XXX: [Title]
**Severity**: Medium  
**Priority**: P2  
**Status**: [Open/In Progress/Fixed/Closed]

[Copy format from INS-001 above]

---

## Low Priority Bugs (P3)

### INS-XXX: [Title]
**Severity**: Low  
**Priority**: P3  
**Status**: [Open/In Progress/Fixed/Closed]

[Copy format from INS-001 above]

---

## Bug Reporting Guidelines

### Severity Definitions

**Critical (Blocker)**:
- System crash or data loss
- Core functionality completely broken
- Security vulnerability
- Affects all users
- No workaround available

**High (Major)**:
- Important feature not working
- Significant performance issue
- Affects many users
- Workaround is difficult

**Medium (Moderate)**:
- Feature works but with issues
- Minor data accuracy problem
- Affects some users
- Reasonable workaround exists

**Low (Minor)**:
- UI/UX issue
- Cosmetic problem
- Typo or translation error
- Affects few users
- Easy workaround

### Priority Definitions

**P0 (Critical)**:
- Must fix before production
- Blocks UAT testing
- Fix within 24 hours

**P1 (High)**:
- Should fix before production
- Doesn't block testing
- Fix within 48 hours

**P2 (Medium)**:
- Can go to production with workaround
- Fix in next sprint
- Fix within 1 week

**P3 (Low)**:
- Nice to have
- Fix when time permits
- Can defer to future release

---

## Bug Report Template (Copy & Use)

```markdown
### INS-XXX: [Short descriptive title]
**Severity**: [Critical/High/Medium/Low]  
**Priority**: [P0/P1/P2/P3]  
**Status**: Open

**Reported By**: [Your Name]  
**Date**: [YYYY-MM-DD HH:MM]  
**Environment**: UAT

**Steps to Reproduce**:
1. Navigate to...
2. Click on...
3. Enter...
4. Observe...

**Expected Result**:
[What should happen]

**Actual Result**:
[What actually happens]

**Impact**:
- [Who is affected]
- [What functionality is blocked]
- [Business impact]

**Screenshots/Video**:
[Attach or link to evidence]

**Browser/Device**:
- Browser: [Chrome/Firefox/Safari] version X
- OS: [Windows/Mac/Linux] version Y
- Screen resolution: 1920x1080

**Additional Notes**:
[Any other relevant information]
[Related bugs: INS-XXX]
[Workaround: ...]

**Status History**:
- [YYYY-MM-DD HH:MM]: Reported
- [YYYY-MM-DD HH:MM]: Status change and notes
```

---

## Known Issues (Not Bugs)

### Issue 1: Slow export with 1000+ employees
**Description**: Excel export takes 15-20 seconds for large reports

**Explanation**: This is expected behavior. Export is processing all calculation data.

**Mitigation**: 
- Use background job for large exports
- Show progress indicator
- Document in user guide

**Status**: Will be enhanced in Phase 2

---

## Won't Fix

### INS-XXX: [Title]
**Reason**: [Out of scope / By design / Too low impact]

**Description**: [Brief description]

**Decision Date**: [YYYY-MM-DD]  
**Decided By**: [Name/Role]

---

## Statistics

### Bugs by Severity
| Severity | Count | % |
|----------|-------|---|
| Critical | 0 | 0% |
| High | 0 | 0% |
| Medium | 0 | 0% |
| Low | 0 | 0% |
| **Total** | **0** | **100%** |

### Bugs by Status
| Status | Count | % |
|--------|-------|---|
| Open | 0 | 0% |
| In Progress | 0 | 0% |
| Fixed (Retest) | 0 | 0% |
| Closed | 0 | 0% |
| Won't Fix | 0 | 0% |
| **Total** | **0** | **100%** |

### Bugs by Component
| Component | Count | % |
|-----------|-------|---|
| Contract Form | 0 | 0% |
| Monthly Report | 0 | 0% |
| Finalization | 0 | 0% |
| Excel Export | 0 | 0% |
| Component Management | 0 | 0% |
| Permissions | 0 | 0% |
| **Total** | **0** | **100%** |

---

## Daily Bug Burn-down

| Date | Open | Fixed | Closed | Net Change |
|------|------|-------|--------|------------|
| 2026-01-15 | 0 | 0 | 0 | 0 |
| 2026-01-16 | 0 | 0 | 0 | 0 |
| ... | | | | |

---

## Testing Progress

### Scenario Completion
| Scenario | Status | Bugs Found | Comments |
|----------|--------|------------|----------|
| 1. Contract Creation | ⬜ Not Started | - | - |
| 2. Report Generation | ⬜ Not Started | - | - |
| 3. Declaration Override | ⬜ Not Started | - | - |
| 4. Report Finalization | ⬜ Not Started | - | - |
| 5. Excel Export | ⬜ Not Started | - | - |
| 6. Component Management | ⬜ Not Started | - | - |
| 7. Data Integrity | ⬜ Not Started | - | - |

**Legend**: ⬜ Not Started | 🔄 In Progress | ✅ Completed | ❌ Blocked

---

## Notes for Testers

### How to Report a Bug Efficiently

1. **Reproduce First**: Try to reproduce the bug at least twice
2. **Check Duplicates**: Search existing bugs to avoid duplicates
3. **Be Specific**: Provide exact steps, not general descriptions
4. **Include Evidence**: Screenshots/videos are very helpful
5. **Note Environment**: Browser, OS, screen size matter
6. **Describe Impact**: Help prioritize by explaining business impact
7. **Suggest Workaround**: If you found one, share it

### Bug Lifecycle

```
[Open] → [In Progress] → [Fixed] → [Retest] → [Closed]
                ↓
         [Won't Fix]
```

1. **Open**: Bug reported and confirmed
2. **In Progress**: Developer working on fix
3. **Fixed**: Fix deployed to UAT
4. **Retest**: Tester verifying the fix
5. **Closed**: Fix verified, bug resolved
6. **Won't Fix**: Decision made not to fix

### Communication Channels

- **Urgent Issues (P0)**: Call Tech Lead immediately
- **Daily Updates**: Slack #insurance-uat channel
- **Bug Details**: Update this document + Jira
- **Questions**: Ask in daily stand-up

---

## Appendix: Common Issues & Solutions

### Issue: Cannot access UAT environment
**Solution**: Check VPN connection, verify credentials

### Issue: Test data not loading
**Solution**: Ask admin to run: `php artisan insurance:seed-test-data`

### Issue: Cache showing old data
**Solution**: Hard refresh (Ctrl+Shift+R) or clear browser cache

### Issue: Changes not appearing
**Solution**: Verify you're on UAT environment, not production

---

**Document Version**: 1.0  
**Last Updated**: January 12, 2026  
**Next Review**: Daily during UAT period
