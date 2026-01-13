# Insurance Module - UAT Test Plan & Checklist

## Overview
This document provides a comprehensive test plan for User Acceptance Testing (UAT) of the new Insurance Management Module. HR team members will validate all features match business requirements.

**UAT Duration**: 2-3 weeks  
**Testers**: HR Staff, Payroll Admin, HR Manager  
**Environment**: Staging/UAT Server  
**Test Data**: Pre-populated sample employees

---

## UAT Roles & Responsibilities

### Role 1: HR Staff
**Permissions**: View insurance reports  
**Focus Areas**:
- View monthly reports
- Check employee insurance data
- Review change records

### Role 2: Payroll Admin
**Permissions**: Full insurance management + component configuration  
**Focus Areas**:
- Create monthly reports
- Approve/reject changes
- Finalize reports
- Export to Excel
- Manage component rates

### Role 3: HR Manager
**Permissions**: View + approve reports  
**Focus Areas**:
- Review finalized reports
- Verify contribution calculations
- Approve export data

---

## Test Environment Setup

### Prerequisites Checklist
- [ ] UAT server accessible
- [ ] Test accounts created for each role
- [ ] Sample data loaded (at least 50 employees)
- [ ] Test scenarios documented
- [ ] Feedback form accessible
- [ ] Bug tracking system ready

### Test Data Requirements
```
Minimum Test Data:
├─ 50+ Employees with active contracts
├─ 10+ Employees with terminated contracts (this month)
├─ 5+ Employees with salary adjustments
├─ All 5 insurance components configured
├─ At least 1 draft monthly report
└─ At least 1 finalized report (for viewing)
```

---

## UAT Test Scenarios

### Scenario 1: Contract Creation with Insurance
**User Role**: HR Staff (via Contract Module)  
**Duration**: 15 minutes  
**Prerequisites**: Employee created

#### Test Steps
1. Navigate to Contracts → Create New Contract
2. Fill in contract details (start date, position, salary)
3. Set Insurance Salary (e.g., 15,000,000 VND)
4. Enable insurance components:
   - ☑ BHXH Hưu trí
   - ☑ BHXH Ốm đau
   - ☑ BHXH TNLĐ-BNN
   - ☑ BHTN (test both base type options)
   - ☑ BHYT
5. Submit contract

#### Expected Results
- ✅ Contract created successfully
- ✅ Insurance participation auto-created
- ✅ All 5 components saved with correct rates
- ✅ BHTN base type saved correctly
- ✅ Legacy boolean fields synced

#### Test Cases
| Test Case | Input | Expected Output | Pass/Fail | Notes |
|-----------|-------|-----------------|-----------|-------|
| TC1.1 - Create with all components | All 5 enabled | All saved | ⬜ | |
| TC1.2 - Create with BHTN fixed amount | BHTN = 5,000,000 | Amount saved | ⬜ | |
| TC1.3 - Create with only BHXH | 3 BHXH enabled | 3 components | ⬜ | |
| TC1.4 - Edit contract, change components | Disable BHTN | Updated | ⬜ | |

---

### Scenario 2: Monthly Report Generation
**User Role**: Payroll Admin  
**Duration**: 20 minutes  
**Prerequisites**: Multiple contracts created/terminated this month

#### Test Steps
1. Navigate to Quản lý BHXH → Báo cáo BHXH
2. Click "Tạo báo cáo mới"
3. Select month and year
4. Submit to generate report
5. Wait for report to be generated
6. Review the 3 tabs:
   - Tab 1: TĂNG LAO ĐỘNG
   - Tab 2: GIẢM
   - Tab 3: ĐIỀU CHỈNH

#### Expected Results
- ✅ Report generated within 30 seconds
- ✅ All new contracts appear in Tab 1
- ✅ Terminated contracts appear in Tab 2
- ✅ Salary adjustments appear in Tab 3
- ✅ Suggested declaration month is correct
- ✅ Employee info displays correctly

#### Test Cases
| Test Case | Scenario | Expected Output | Pass/Fail | Notes |
|-----------|----------|-----------------|-----------|-------|
| TC2.1 - Generate current month | Jan 2026 | Success | ⬜ | |
| TC2.2 - Generate past month | Dec 2025 | Success | ⬜ | |
| TC2.3 - No changes in month | Empty month | 0 records | ⬜ | |
| TC2.4 - Large volume | 100+ changes | < 30s | ⬜ | |

---

### Scenario 3: Declaration Month Override
**User Role**: Payroll Admin  
**Duration**: 10 minutes  
**Prerequisites**: Draft report with records

#### Test Steps
1. Open a draft report
2. In TĂNG LAO ĐỘNG tab, find a record
3. Note the "Tháng KK gợi ý" (suggested month)
4. Change "Tháng KK chính thức" to different month
5. Try to save WITHOUT entering reason
6. Enter reason: "Nhân viên ký hợp đồng cuối tháng"
7. Save changes

#### Expected Results
- ✅ Cannot save without reason (validation error)
- ✅ Red border appears on reason field
- ✅ After entering reason, saves successfully
- ✅ Yellow warning icon appears
- ✅ Toast notification shows success

#### Test Cases
| Test Case | Input | Expected Output | Pass/Fail | Notes |
|-----------|-------|-----------------|-----------|-------|
| TC3.1 - Change month without reason | No reason | Error | ⬜ | |
| TC3.2 - Change with reason | Valid reason | Success | ⬜ | |
| TC3.3 - Revert to suggested | Same as suggested | No reason required | ⬜ | |
| TC3.4 - View reason later | - | Reason displayed | ⬜ | |

---

### Scenario 4: Report Finalization
**User Role**: Payroll Admin  
**Duration**: 15 minutes  
**Prerequisites**: Draft report with all data reviewed

#### Test Steps
1. Open draft report
2. Review all tabs for accuracy
3. Click "Hoàn tất báo cáo" button
4. Confirm finalization
5. Wait for snapshot creation
6. Navigate to "TỔNG HỢP ĐÓNG BHXH" tab (now enabled)
7. Review contribution summary

#### Expected Results
- ✅ Finalization completes within 30 seconds
- ✅ Report status changes to FINALIZED
- ✅ Tabs 1-3 become read-only
- ✅ Tab 4 is now accessible
- ✅ All employees have contribution records
- ✅ Component amounts calculated correctly
- ✅ Totals match expectations

#### Test Cases
| Test Case | Scenario | Expected Output | Pass/Fail | Notes |
|-----------|----------|-----------------|-----------|-------|
| TC4.1 - Finalize small report | < 50 employees | < 5s | ⬜ | |
| TC4.2 - Finalize medium report | 50-200 employees | < 15s | ⬜ | |
| TC4.3 - View snapshot tab | After finalized | Data visible | ⬜ | |
| TC4.4 - Cannot edit finalized | Try to edit | Disabled | ⬜ | |

---

### Scenario 5: Excel Export
**User Role**: Payroll Admin  
**Duration**: 10 minutes  
**Prerequisites**: Finalized report

#### Test Steps
1. Open finalized report
2. Go to "TỔNG HỢP ĐÓNG BHXH" tab
3. Click "Xuất Excel" button
4. Wait for file download
5. Open Excel file
6. Verify data:
   - Employee list complete
   - 5 component columns present
   - Amounts match UI
   - Totals calculated correctly
   - Formatting is professional

#### Expected Results
- ✅ Excel downloads within 10 seconds
- ✅ File opens without errors
- ✅ All data matches UI display
- ✅ Formatting is clean and readable
- ✅ Totals row included
- ✅ File naming: BaoCao_BHXH_YYYY_MM.xlsx

#### Test Cases
| Test Case | Scenario | Expected Output | Pass/Fail | Notes |
|-----------|----------|-----------------|-----------|-------|
| TC5.1 - Export small report | < 50 rows | < 3s | ⬜ | |
| TC5.2 - Export medium report | 50-200 rows | < 10s | ⬜ | |
| TC5.3 - Open in Excel | - | No errors | ⬜ | |
| TC5.4 - Verify calculations | Manual check | Correct | ⬜ | |

---

### Scenario 6: Component Rate Management
**User Role**: Payroll Admin  
**Duration**: 15 minutes  
**Prerequisites**: Access to component management

#### Test Steps
1. Navigate to Quản lý BHXH → Cấu hình BHXH
2. Review all 5 components with current rates
3. Click edit button for "BHXH Hưu trí"
4. Change rate_employee from 8% to 8.5%
5. Observe rate_total auto-update
6. Read warning message about future contracts
7. Save changes
8. Verify rates updated
9. Create new contract → verify new rates applied
10. Check old contract → verify old rates retained

#### Expected Results
- ✅ Component list displays correctly
- ✅ Edit dialog opens smoothly
- ✅ Rate_total calculates automatically
- ✅ Warning message clear and visible
- ✅ Changes save successfully
- ✅ New contracts use new rates
- ✅ Existing contracts keep old rates

#### Test Cases
| Test Case | Input | Expected Output | Pass/Fail | Notes |
|-----------|-------|-----------------|-----------|-------|
| TC6.1 - View all components | - | 5 components | ⬜ | |
| TC6.2 - Edit rates | 8% → 8.5% | Saved | ⬜ | |
| TC6.3 - Auto-calculate total | Employee+Employer | Sum correct | ⬜ | |
| TC6.4 - New contract uses new rate | Create contract | 8.5% | ⬜ | |
| TC6.5 - Old contract unchanged | View old | 8% | ⬜ | |
| TC6.6 - Toggle is_active | Disable BHTN | Not shown in forms | ⬜ | |

---

### Scenario 7: Data Integrity Check
**User Role**: System Admin (via Terminal)  
**Duration**: 5 minutes  
**Prerequisites**: SSH access to server

#### Test Steps
1. SSH to UAT server
2. Navigate to project directory
3. Run: `php artisan insurance:check-integrity`
4. Review output for any issues
5. If fixable issues exist, run with `--fix`
6. Re-run to verify clean

#### Expected Results
- ✅ Command executes without errors
- ✅ Clear output with issue summary
- ✅ Auto-fix works for orphaned data
- ✅ Manual issues clearly identified
- ✅ "No issues found" message when clean

#### Test Cases
| Test Case | Scenario | Expected Output | Pass/Fail | Notes |
|-----------|----------|-----------------|-----------|-------|
| TC7.1 - Clean data | No issues | "0 issues found" | ⬜ | |
| TC7.2 - With orphaned data | Create orphan | Detected + fixed | ⬜ | |
| TC7.3 - Invalid rates | Bad rate_total | Detected + fixed | ⬜ | |

---

## Usability Testing Checklist

### Navigation & UI
- [ ] Menu items easy to find
- [ ] Breadcrumbs help orientation
- [ ] Back button works correctly
- [ ] Page titles clear and accurate
- [ ] Icons intuitive and consistent

### Forms & Inputs
- [ ] Form labels clear in Vietnamese
- [ ] Required fields marked with *
- [ ] Validation messages helpful
- [ ] Error messages in Vietnamese
- [ ] Success messages encouraging

### Tables & Data Display
- [ ] Column headers descriptive
- [ ] Data aligned properly
- [ ] Pagination works smoothly
- [ ] Sorting works (if applicable)
- [ ] Loading states visible

### Performance
- [ ] Pages load within 3 seconds
- [ ] Actions complete within 5 seconds
- [ ] No freezing or hanging
- [ ] Smooth scrolling
- [ ] Responsive on different screen sizes

---

## Bug Tracking Template

### Bug Report Format
```
BUG ID: INS-XXX
Title: [Short description]
Severity: Critical / High / Medium / Low
Priority: P0 / P1 / P2 / P3

Reported By: [Name]
Date: [YYYY-MM-DD]
Environment: UAT

Steps to Reproduce:
1. ...
2. ...
3. ...

Expected Result:
[What should happen]

Actual Result:
[What actually happens]

Screenshots/Video:
[Attach evidence]

Additional Notes:
[Any other relevant info]

Status: Open / In Progress / Fixed / Closed
```

---

## Feedback Collection Form

### Feature Feedback Template

**Feature**: Contract Insurance Components

**Questions**:
1. Is the feature easy to understand? (1-5) ⭐⭐⭐⭐⭐
2. Does it match your workflow? (Yes/No/Partially)
3. Are there any missing features?
4. Suggestions for improvement:
5. Overall satisfaction: (1-5) ⭐⭐⭐⭐⭐

**Comments**:
```
[Free-form feedback]
```

---

## UAT Sign-off Criteria

### Must-Pass Criteria (Blockers)
- ✅ All critical bugs resolved
- ✅ Core workflows functional (create contract, generate report, finalize, export)
- ✅ Data accuracy verified (calculations correct)
- ✅ No data loss scenarios
- ✅ Security permissions working

### Should-Pass Criteria (Major)
- ✅ All high-priority bugs resolved
- ✅ Usability issues addressed
- ✅ Performance acceptable (< 30s operations)
- ✅ Vietnamese translations correct
- ✅ Error handling adequate

### Nice-to-Have Criteria (Minor)
- ✅ All medium-priority bugs resolved
- ✅ UI polished and consistent
- ✅ Help text/tooltips present
- ✅ Keyboard shortcuts work
- ✅ Mobile responsive (if required)

---

## UAT Schedule

### Week 1: Initial Testing
**Focus**: Core functionality

- **Day 1-2**: Contract creation with insurance (Scenario 1)
- **Day 3-4**: Monthly report generation (Scenario 2)
- **Day 5**: Declaration month override (Scenario 3)

**Deliverable**: Initial bug list

### Week 2: Advanced Testing
**Focus**: Full workflow

- **Day 1-2**: Report finalization (Scenario 4)
- **Day 3**: Excel export (Scenario 5)
- **Day 4-5**: Component management (Scenario 6)

**Deliverable**: Complete test results

### Week 3: Regression & Sign-off
**Focus**: Verify fixes and finalize

- **Day 1-2**: Retest all fixed bugs
- **Day 3**: Performance testing at scale
- **Day 4**: Data integrity validation
- **Day 5**: Final sign-off meeting

**Deliverable**: UAT approval or issue list

---

## UAT Meeting Schedule

### Daily Stand-up (15 min)
**Time**: 9:00 AM  
**Attendees**: All testers + Dev team

**Agenda**:
- Completed tests yesterday
- Planned tests today
- Blockers/issues

### Weekly Review (1 hour)
**Time**: Friday 2:00 PM  
**Attendees**: All testers + Dev team + Stakeholders

**Agenda**:
- Test progress summary
- Critical bugs review
- Risk assessment
- Next week planning

### Final Sign-off Meeting (2 hours)
**Time**: End of Week 3  
**Attendees**: All stakeholders

**Agenda**:
- UAT results presentation
- Outstanding issues review
- Go/No-Go decision
- Production deployment planning

---

## Risk Management

### High Risk Items
1. **Data Migration Issues**
   - Risk: Old data doesn't match new structure
   - Mitigation: Run backfill commands on test data first

2. **Performance at Scale**
   - Risk: Slow with 1000+ employees
   - Mitigation: Load test before UAT

3. **Permission Conflicts**
   - Risk: Users see data they shouldn't
   - Mitigation: Test all role combinations

### Medium Risk Items
1. **Browser Compatibility**
   - Risk: Issues on IE/Safari
   - Mitigation: Test on all target browsers

2. **Excel Format Issues**
   - Risk: Files don't open properly
   - Mitigation: Test with multiple Excel versions

### Low Risk Items
1. **UI Translation Errors**
   - Risk: Incorrect Vietnamese
   - Mitigation: Native speaker review

2. **Minor UX Issues**
   - Risk: Confusing labels
   - Mitigation: Collect feedback and iterate

---

## Success Metrics

### Quantitative Metrics
- ✅ 90%+ test scenarios passed
- ✅ 0 critical bugs remaining
- ✅ < 5 high-priority bugs remaining
- ✅ 100% of core workflows functional
- ✅ Average user satisfaction ≥ 4/5

### Qualitative Metrics
- ✅ HR team comfortable using the system
- ✅ Positive feedback on usability
- ✅ Confidence in data accuracy
- ✅ Workflow efficiency improved
- ✅ Training materials adequate

---

## Post-UAT Actions

### Before Production
- [ ] Fix all critical and high-priority bugs
- [ ] Update documentation based on feedback
- [ ] Conduct training sessions
- [ ] Prepare rollback plan
- [ ] Set up production monitoring

### After Production Launch
- [ ] Monitor for issues first week
- [ ] Collect user feedback
- [ ] Address quick wins
- [ ] Plan future enhancements
- [ ] Document lessons learned

---

## Contact Information

### Support During UAT
- **Technical Lead**: [Name] - [Email] - [Phone]
- **Product Owner**: [Name] - [Email] - [Phone]
- **UAT Coordinator**: [Name] - [Email] - [Phone]

### Bug Reporting
- **Jira**: [Project URL]
- **Slack**: #insurance-uat
- **Email**: uat-insurance@company.com

---

## Appendix A: Test Data Script

Create sample data for testing:
```bash
php artisan insurance:seed-test-data --employees=50
```

## Appendix B: Quick Reference

### Key URLs (UAT Environment)
- Contract Form: /contracts/create
- Insurance Reports: /insurance-reports
- Component Management: /insurance-components/manage

### Common Commands
```bash
# Check integrity
php artisan insurance:check-integrity

# Run benchmark
php artisan insurance:benchmark

# Clear cache
php artisan cache:clear
```

---

**Document Version**: 1.0  
**Last Updated**: January 12, 2026  
**Next Review**: After UAT completion
