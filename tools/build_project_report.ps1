[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$outputPath = Join-Path $root 'HR Project Fair Copy.docx'
$pdfPath = Join-Path $root 'HR Project Fair Copy.pdf'
$logPath = Join-Path $root 'report_build.log'
$dfdImagePath = Join-Path $root 'ocr\design\page-4.png'
$erImagePath = Join-Path $root 'ocr\design\page-5.png'
$appendixImages = @(
    @{ Path = (Join-Path $root 'screens\landing.png'); Caption = 'Landing Page' },
    @{ Path = (Join-Path $root 'screens\login.png'); Caption = 'Secure Login Page' }
) | Where-Object { Test-Path $_.Path }

$wdCollapseEnd = 0
$wdPageBreak = 7
$wdSectionBreakNextPage = 2
$wdAlignLeft = 0
$wdAlignCenter = 1
$wdAlignJustify = 3
$wdLineSpaceSingle = 0
$wdLineSpaceDouble = 2
$wdLineSpaceMultiple = 5
$wdExportFormatPDF = 17

$word = $null
$doc = $null
$selection = $null

function Log-Step {
    param([string]$Message)
    $stamp = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
    Add-Content -Path $script:logPath -Value "[$stamp] $Message"
}

function Move-ToDocumentEnd {
    $end = [Math]::Max(0, $script:doc.Content.End - 1)
    $script:selection.SetRange($end, $end)
}

function Set-ParagraphStyle {
    param(
        [string]$FontName = 'Times New Roman',
        [double]$FontSize = 13,
        [bool]$Bold = $false,
        [int]$Alignment = $wdAlignJustify,
        [double]$SpaceBefore = 0,
        [double]$SpaceAfter = 14.35,
        [double]$LineSpacing = 13.8,
        [int]$LineSpacingRule = $wdLineSpaceMultiple,
        [double]$LeftIndent = 0,
        [double]$FirstLineIndent = 36
    )

    $script:selection.Font.Name = $FontName
    $script:selection.Font.Size = $FontSize
    $script:selection.Font.Bold = if ($Bold) { -1 } else { 0 }
    $script:selection.ParagraphFormat.Alignment = $Alignment
    $script:selection.ParagraphFormat.SpaceBefore = $SpaceBefore
    $script:selection.ParagraphFormat.SpaceAfter = $SpaceAfter
    $script:selection.ParagraphFormat.LineSpacingRule = $LineSpacingRule
    $script:selection.ParagraphFormat.LineSpacing = $LineSpacing
    $script:selection.ParagraphFormat.LeftIndent = $LeftIndent
    $script:selection.ParagraphFormat.FirstLineIndent = $FirstLineIndent
}

function Add-Paragraph {
    param(
        [string]$Text = '',
        [string]$FontName = 'Times New Roman',
        [double]$FontSize = 13,
        [bool]$Bold = $false,
        [int]$Alignment = $wdAlignJustify,
        [double]$SpaceBefore = 0,
        [double]$SpaceAfter = 14.35,
        [double]$LineSpacing = 13.8,
        [int]$LineSpacingRule = $wdLineSpaceMultiple,
        [double]$LeftIndent = 0,
        [double]$FirstLineIndent = 36
    )

    Move-ToDocumentEnd
    Set-ParagraphStyle -FontName $FontName -FontSize $FontSize -Bold $Bold -Alignment $Alignment `
        -SpaceBefore $SpaceBefore -SpaceAfter $SpaceAfter -LineSpacing $LineSpacing `
        -LineSpacingRule $LineSpacingRule -LeftIndent $LeftIndent -FirstLineIndent $FirstLineIndent

    if ($Text -ne '') {
        $script:selection.TypeText($Text)
    }

    $script:selection.TypeParagraph()
}

function Add-ParagraphBlock {
    param(
        [string[]]$Lines,
        [string]$FontName = 'Times New Roman',
        [double]$FontSize = 13,
        [bool]$Bold = $false,
        [int]$Alignment = $wdAlignJustify,
        [double]$SpaceBefore = 0,
        [double]$SpaceAfter = 14.35,
        [double]$LineSpacing = 13.8,
        [int]$LineSpacingRule = $wdLineSpaceMultiple,
        [double]$LeftIndent = 0,
        [double]$FirstLineIndent = 36
    )

    foreach ($line in $Lines) {
        Add-Paragraph -Text $line -FontName $FontName -FontSize $FontSize -Bold $Bold -Alignment $Alignment `
            -SpaceBefore $SpaceBefore -SpaceAfter $SpaceAfter -LineSpacing $LineSpacing `
            -LineSpacingRule $LineSpacingRule -LeftIndent $LeftIndent -FirstLineIndent $FirstLineIndent
    }
}

function Add-BlankLines {
    param([int]$Count)
    for ($i = 0; $i -lt $Count; $i++) {
        Add-Paragraph -Text '' -SpaceAfter 0 -FirstLineIndent 0
    }
}

function Add-PageBreak {
    Move-ToDocumentEnd
    $script:selection.InsertBreak($wdPageBreak)
}

function Add-SectionBreak {
    Move-ToDocumentEnd
    $script:selection.InsertBreak($wdSectionBreakNextPage)
}

function Add-ChapterTitlePage {
    param([string]$Title)
    Add-BlankLines -Count 16
    Add-Paragraph -Text $Title -FontSize 24 -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 36
    Add-BlankLines -Count 16
}

function Reset-SectionFooter {
    param([int]$SectionIndex)
    $section = $script:doc.Sections.Item($SectionIndex)
    $section.Headers.Item(1).LinkToPrevious = $false
    $section.Footers.Item(1).LinkToPrevious = $false
    $section.Footers.Item(1).Range.Text = ''
}

function Disable-SectionPageNumbers {
    param([int]$SectionIndex)
    Reset-SectionFooter -SectionIndex $SectionIndex
}

function Enable-SectionPageNumbers {
    param(
        [int]$SectionIndex,
        [int]$StartingNumber
    )

    $section = $script:doc.Sections.Item($SectionIndex)
    $section.Headers.Item(1).LinkToPrevious = $false
    $section.Footers.Item(1).LinkToPrevious = $false
    $section.Footers.Item(1).Range.Text = ''
    $section.Footers.Item(1).Range.ParagraphFormat.Alignment = $wdAlignCenter
    $section.Footers.Item(1).Range.Font.Name = 'Calibri'
    $section.Footers.Item(1).Range.Font.Size = 11
    $section.Footers.Item(1).PageNumbers.RestartNumberingAtSection = $true
    $section.Footers.Item(1).PageNumbers.StartingNumber = $StartingNumber
    $null = $section.Footers.Item(1).PageNumbers.Add()
}

function Add-Image {
    param(
        [string]$Path,
        [double]$Width = 451.3,
        [string]$Caption = ''
    )

    if (-not (Test-Path $Path)) {
        return
    }

    Move-ToDocumentEnd
    Set-ParagraphStyle -Alignment $wdAlignCenter -FontName 'Times New Roman' -FontSize 13 -Bold $false -SpaceAfter 8 -FirstLineIndent 0
    $shape = $script:selection.InlineShapes.AddPicture($Path)
    $shape.LockAspectRatio = -1
    $shape.Width = $Width
    $script:selection.TypeParagraph()

    if ($Caption -ne '') {
        Add-Paragraph -Text $Caption -FontSize 11 -Alignment $wdAlignCenter -SpaceAfter 6 -FirstLineIndent 0
    }
}

function Add-TableDesign {
    param(
        [string]$TableNumber,
        [string]$TableName,
        [string]$PrimaryKey,
        [string]$ForeignKey,
        [object[]]$Fields
    )

    Add-Paragraph -Text "$TableNumber. Table Name: $TableName" -Bold $true -Alignment $wdAlignLeft -SpaceAfter 7.9 -FirstLineIndent 0
    Add-Paragraph -Text "Primary Key: $PrimaryKey" -Alignment $wdAlignLeft -SpaceAfter 4 -FirstLineIndent 0
    if ($ForeignKey -ne '') {
        Add-Paragraph -Text "Foreign Key: $ForeignKey" -Alignment $wdAlignLeft -SpaceAfter 6 -FirstLineIndent 0
    }

    Move-ToDocumentEnd
    $range = $script:selection.Range
    $table = $script:doc.Tables.Add($range, $Fields.Count + 1, 4)
    $table.Borders.Enable = 1
    $table.Range.Font.Name = 'Times New Roman'
    $table.Range.Font.Size = 10
    $table.Rows.Item(1).Range.Font.Bold = -1
    $table.Cell(1, 1).Range.Text = 'Field Name'
    $table.Cell(1, 2).Range.Text = 'Data Type'
    $table.Cell(1, 3).Range.Text = 'Size'
    $table.Cell(1, 4).Range.Text = 'Description'

    for ($row = 0; $row -lt $Fields.Count; $row++) {
        $table.Cell($row + 2, 1).Range.Text = [string]$Fields[$row][0]
        $table.Cell($row + 2, 2).Range.Text = [string]$Fields[$row][1]
        $table.Cell($row + 2, 3).Range.Text = [string]$Fields[$row][2]
        $table.Cell($row + 2, 4).Range.Text = [string]$Fields[$row][3]
    }

    try {
        $table.AutoFitBehavior(1) | Out-Null
    } catch {
    }

    $end = $table.Range.End
    $script:selection.SetRange($end, $end)
    $script:selection.TypeParagraph()
}

$tableDesigns = @(
    @{
        Number = '1'
        Name = 'User Details'
        PrimaryKey = 'id'
        ForeignKey = ''
        Fields = @(
            @('id', 'int', '10', 'Unique user identification number'),
            @('username', 'varchar', '100', 'Username used for authentication'),
            @('email', 'varchar', '150', 'Official email address'),
            @('password_hash', 'varchar', '255', 'Encrypted password value'),
            @('full_name', 'varchar', '150', 'Full name of the user'),
            @('status', 'tinyint', '1', 'Account active or inactive'),
            @('created_at', 'timestamp', '-', 'Record creation date and time')
        )
    },
    @{
        Number = '2'
        Name = 'Employee Details'
        PrimaryKey = 'id'
        ForeignKey = 'user_id'
        Fields = @(
            @('id', 'int', '11', 'Unique employee identification number'),
            @('user_id', 'int', '10', 'Linked login account'),
            @('employee_code', 'varchar', '50', 'Employee reference code'),
            @('first_name', 'varchar', '100', 'Employee first name'),
            @('last_name', 'varchar', '100', 'Employee last name'),
            @('department', 'varchar', '200', 'Assigned department'),
            @('designation', 'varchar', '100', 'Job role or designation'),
            @('date_of_joining', 'date', '-', 'Employee joining date'),
            @('salary', 'decimal', '10,2', 'Basic salary information')
        )
    },
    @{
        Number = '3'
        Name = 'Attendance Details'
        PrimaryKey = 'id'
        ForeignKey = 'employee_id'
        Fields = @(
            @('id', 'int', '11', 'Attendance identification number'),
            @('employee_id', 'int', '11', 'Linked employee record'),
            @('attendance_date', 'date', '-', 'Attendance date'),
            @('check_in', 'time', '-', 'Punch-in time'),
            @('check_out', 'time', '-', 'Punch-out time'),
            @('status', 'enum', '-', 'Present, absent, late or half day'),
            @('created_at', 'timestamp', '-', 'Record creation timestamp')
        )
    },
    @{
        Number = '4'
        Name = 'Loan Details'
        PrimaryKey = 'id'
        ForeignKey = 'employee_id, created_by'
        Fields = @(
            @('id', 'int', '10', 'Loan identification number'),
            @('employee_id', 'int', '11', 'Employee who requested the loan'),
            @('requested_amount', 'decimal', '12,2', 'Requested loan amount'),
            @('approved_amount', 'decimal', '12,2', 'Sanctioned amount'),
            @('interest_rate', 'decimal', '5,2', 'Interest percentage'),
            @('tenure_months', 'int', '11', 'Repayment period in months'),
            @('emi_amount', 'decimal', '12,2', 'Calculated EMI amount'),
            @('status', 'enum', '-', 'Pending, approved, rejected or closed')
        )
    },
    @{
        Number = '5'
        Name = 'Loan Repayment Details'
        PrimaryKey = 'id'
        ForeignKey = 'loan_id'
        Fields = @(
            @('id', 'int', '10', 'Repayment identification number'),
            @('loan_id', 'int', '10', 'Linked loan record'),
            @('due_date', 'date', '-', 'Scheduled due date'),
            @('paid_date', 'date', '-', 'Actual payment date'),
            @('amount_due', 'decimal', '12,2', 'Amount expected to be paid'),
            @('amount_paid', 'decimal', '12,2', 'Amount actually received'),
            @('payment_status', 'enum', '-', 'Pending, paid, overdue or partial'),
            @('payment_mode', 'enum', '-', 'Cash, bank transfer, UPI or salary deduction')
        )
    },
    @{
        Number = '6'
        Name = 'Payroll Details'
        PrimaryKey = 'id'
        ForeignKey = 'employee_id'
        Fields = @(
            @('id', 'int', '11', 'Payroll identification number'),
            @('employee_id', 'int', '11', 'Linked employee record'),
            @('salary_month', 'varchar', '20', 'Payroll month'),
            @('basic_salary', 'decimal', '10,2', 'Basic salary'),
            @('allowances', 'decimal', '10,2', 'Allowance amount'),
            @('bonus', 'decimal', '10,2', 'Bonus value'),
            @('leave_deduction', 'decimal', '10,2', 'Deduction for leave'),
            @('loan_emi_deduction', 'decimal', '10,2', 'Loan EMI deduction'),
            @('net_salary', 'decimal', '10,2', 'Net salary payable')
        )
    }
)

try {
    if (Test-Path $logPath) {
        Remove-Item $logPath -Force
    }
    Log-Step 'Starting report build.'
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0
    $doc = $word.Documents.Add()
    $selection = $word.Selection
    Log-Step 'Word document created.'

    $doc.PageSetup.TopMargin = 72
    $doc.PageSetup.BottomMargin = 72
    $doc.PageSetup.LeftMargin = 72
    $doc.PageSetup.RightMargin = 72
    $doc.PageSetup.PageWidth = 595.3
    $doc.PageSetup.PageHeight = 841.9

    $sections = [ordered]@{}
    $sections.Content = 1

    Disable-SectionPageNumbers -SectionIndex 1

    Add-BlankLines -Count 18
    Add-Paragraph -Text 'CONTENT' -FontSize 24 -Bold $true -Alignment $wdAlignCenter -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'CONTENT                                                                                                         Page. No.' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 2 -FirstLineIndent 0
    Add-Paragraph -Text '1. INTRODUCTION                                                                                                      1' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '1.1 OVERVIEW OF THE SYSTEM' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '1.2 MODULE DESCRIPTION' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '1.3 SYSTEM SPECIFICATION' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '1.3.1 HARDWARE REQUIREMENTS' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '1.3.2 SOFTWARE REQUIREMENTS' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '1.4 ABOUT THE SOFTWARE' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '1.4.1 FRONT END' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '1.4.2 BACK END' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '2. SYSTEM ANALYSIS                                                                                                  9' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '2.1 EXISTING SYSTEM' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '2.1.1 DISADVANTAGES OF EXISTING SYSTEM' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '2.2 PROPOSED SYSTEM' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '2.2.1 ADVANTAGES OF PROPOSED SYSTEM' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '3. SYSTEM DESIGN                                                                                                   11' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '3.1 DESIGN NOTATION' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '3.1.1 DATA FLOW DIAGRAM' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '3.1.2 ENTITY RELATIONSHIP DIAGRAM' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '3.2 DESIGN PROCESS' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '3.2.1 INPUT DESIGN' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '3.2.2 DATABASE DESIGN' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '3.2.3 TABLE DESIGN' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '3.2.4 OUTPUT DESIGN' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '4. SYSTEM TESTING AND IMPLEMENTATION                                                                      21' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '4.1 SYSTEM TESTING' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '4.2 IMPLEMENTATION' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '5. CONCLUSION AND FUTURE ENHANCEMENT                                                             24' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text '6. BIBLIOGRAPHY                                                                                                  26' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0
    Add-Paragraph -Text 'APPENDIX                                                                                                          27   SAMPLE SCREENS' -FontSize 10 -Alignment $wdAlignLeft -SpaceAfter 0 -FirstLineIndent 0

    Add-SectionBreak
    $sections.Abstract = $doc.Sections.Count
    Disable-SectionPageNumbers -SectionIndex $sections.Abstract
    Log-Step 'Content page completed.'

    Add-BlankLines -Count 6
    Add-Paragraph -Text 'ABSTRACT' -FontSize 18 -Bold $true -Alignment $wdAlignCenter -FirstLineIndent 0 -SpaceAfter 12
    Add-Paragraph -Text 'The project entitled "Dynamic Interaction Web Portal for Human Resource and Employee Credit Repay Process" is developed to organize employee administration and loan repayment activities inside an organization through a single web-based platform.' -SpaceAfter 14.35
    Add-Paragraph -Text 'The application supports employee records, attendance, leave management, payroll generation, employee performance review, membership tracking, loan issue, repayment monitoring, internal communication, and notification handling. The frontend is built using HTML, CSS, JavaScript, Bootstrap, and responsive layouts, while the backend is implemented in PHP with a MySQL database.' -SpaceAfter 14.35
    Add-Paragraph -Text 'The main objective of the proposed system is to reduce manual work in the human resource and finance departments by integrating all employee-related and credit-recovery processes into one secure portal. The system provides role-based access for administrators, HR managers, finance managers, and employees so that each user can access only the functions related to their work.' -SpaceAfter 14.35
    Add-Paragraph -Text 'By computerizing loan applications, approval flow, repayment schedules, payroll-linked deductions, and communication between employees and administrators, the project improves transparency, saves time, and increases data accuracy. The system also helps the organization generate reports quickly and maintain secure, centralized records for future reference.' -SpaceAfter 14.35

    Add-SectionBreak
    $sections.IntroTitle = $doc.Sections.Count
    Disable-SectionPageNumbers -SectionIndex $sections.IntroTitle
    Add-ChapterTitlePage -Title 'INTRODUCTION'

    Add-SectionBreak
    $sections.IntroContent = $doc.Sections.Count
    Enable-SectionPageNumbers -SectionIndex $sections.IntroContent -StartingNumber 1
    Log-Step 'Introduction section started.'

    Add-Paragraph -Text '1. INTRODUCTION' -FontSize 13 -Bold $true -Alignment $wdAlignJustify -SpaceAfter 8.1 -FirstLineIndent 36.7
    Add-Paragraph -Text '1.1 OVERVIEW OF THE SYSTEM' -FontSize 12 -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'In modern organizations, human resource administration and employee credit recovery activities require continuous coordination between HR teams, finance teams, and employees. When these activities are maintained through registers, spreadsheets, and disconnected communication channels, it becomes difficult to track attendance, leave, payroll deductions, loan approvals, and repayment status accurately.' -SpaceAfter 14.35
    Add-Paragraph -Text 'The Dynamic Interaction Web Portal for Human Resource and Employee Credit Repay Process is designed to provide a centralized and interactive environment for these operations. The application combines employee master management, attendance monitoring, leave handling, payroll processing, performance evaluation, membership maintenance, loan processing, repayment control, and internal communication into one secure web portal.' -SpaceAfter 14.35
    Add-Paragraph -Text 'The system is developed as a role-based application. Administrators supervise the platform, HR managers maintain employee and policy-related activities, finance managers handle loans and repayments, and employees can view or interact with their own records, requests, messages, and notifications. This shared structure improves accountability and reduces delays in decision making.' -SpaceAfter 14.35
    Add-Paragraph -Text 'The project uses PHP for server-side processing and MySQL for database storage. Bootstrap and custom CSS are used to provide responsive user interfaces, while JavaScript supports validation and interactive actions. The application is intended to run in a local or intranet environment through XAMPP, making deployment practical for medium-scale organizations.' -SpaceAfter 14.35
    Add-Paragraph -Text 'By automating routine operations and maintaining all records in one database, the system supports fast reporting, improves data consistency, and creates a transparent channel for employee services and credit repayment follow-up.' -SpaceAfter 14.35
    Add-Paragraph -Text 'MODULES:' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 14.5 -FirstLineIndent 0
    Add-ParagraphBlock -Lines @(
        '1. Authentication and Role Management Module',
        '2. Employee Information and Attendance Module',
        '3. Leave and Payroll Management Module',
        '4. Loan Application and Repayment Module',
        '5. Performance and Promotion Module',
        '6. Membership and Communication Module',
        '7. Notification and Reporting Module'
    ) -Bold $true -Alignment $wdAlignLeft -SpaceBefore 5 -SpaceAfter 5 -LineSpacingRule $wdLineSpaceDouble -LineSpacing 24 -LeftIndent 36.7 -FirstLineIndent 0

    Add-PageBreak

    Add-Paragraph -Text '2' -FontName 'Calibri' -FontSize 11 -Bold $true -Alignment $wdAlignCenter -SpaceAfter 14.5 -FirstLineIndent 0
    Add-Paragraph -Text '1.2 MODULE DESCRIPTION' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 14.5 -FirstLineIndent 0
    Add-Paragraph -Text '1. Authentication and Role Management Module:' -Bold $true -Alignment $wdAlignJustify -SpaceAfter 6.3 -LeftIndent 18 -FirstLineIndent -18
    Add-Paragraph -Text 'This module manages secure login, session handling, user accounts, and role mapping for administrator, HR manager, finance manager, and employee users. Passwords are stored in encrypted form and access is controlled according to the assigned role.' -LeftIndent 0 -FirstLineIndent 18 -SpaceAfter 8
    Add-Paragraph -Text '2. Employee Information and Attendance Module:' -Bold $true -Alignment $wdAlignJustify -SpaceAfter 6.3 -LeftIndent 18 -FirstLineIndent -18
    Add-Paragraph -Text 'This module maintains employee identity, department, designation, salary data, and joining information. It also records attendance date, punch-in, punch-out, and status details so that HR and payroll calculations can be performed correctly.' -LeftIndent 0 -FirstLineIndent 18 -SpaceAfter 8
    Add-Paragraph -Text '3. Leave and Payroll Management Module:' -Bold $true -Alignment $wdAlignJustify -SpaceAfter 6.3 -LeftIndent 18 -FirstLineIndent -18
    Add-Paragraph -Text 'This module handles leave requests, approval decisions, payroll generation, deduction calculation, loan EMI deduction, and payslip creation. It helps the organization maintain salary discipline and accurate monthly settlement records.' -LeftIndent 0 -FirstLineIndent 18 -SpaceAfter 8
    Add-Paragraph -Text '4. Loan Application and Repayment Module:' -Bold $true -Alignment $wdAlignJustify -SpaceAfter 6.3 -LeftIndent 18 -FirstLineIndent -18
    Add-Paragraph -Text 'This module records employee loan requests, approvals, tenure, EMI amounts, repayment schedules, payment modes, and status history. It supports finance teams in tracking pending, paid, overdue, and partially paid repayments.' -LeftIndent 0 -FirstLineIndent 18 -SpaceAfter 8
    Add-Paragraph -Text '5. Performance and Promotion Module:' -Bold $true -Alignment $wdAlignJustify -SpaceAfter 6.3 -LeftIndent 18 -FirstLineIndent -18
    Add-Paragraph -Text 'This module stores monthly evaluation data such as test score, attendance score, overall score, grade, promotion recommendation, and promotion approval details. It helps management review employee growth using measurable criteria.' -LeftIndent 0 -FirstLineIndent 18 -SpaceAfter 8
    Add-Paragraph -Text '6. Membership and Communication Module:' -Bold $true -Alignment $wdAlignJustify -SpaceAfter 6.3 -LeftIndent 18 -FirstLineIndent -18
    Add-Paragraph -Text 'This module captures membership records and provides internal messaging between employees, HR, and finance users. It supports subject-based communication for leave, loan, repayment, attendance, and general updates.' -LeftIndent 0 -FirstLineIndent 18 -SpaceAfter 8
    Add-Paragraph -Text '7. Notification and Reporting Module:' -Bold $true -Alignment $wdAlignJustify -SpaceAfter 6.3 -LeftIndent 18 -FirstLineIndent -18
    Add-Paragraph -Text 'This module generates notifications for important events such as leave requests and repayment updates, and also exports attendance, payroll, loan, and repayment reports. It improves visibility and speeds up administrative follow-up.' -LeftIndent 0 -FirstLineIndent 18 -SpaceAfter 14.5

    Add-PageBreak

    Add-Paragraph -Text '3' -FontName 'Calibri' -FontSize 11 -Bold $true -Alignment $wdAlignCenter -SpaceAfter 14.5 -FirstLineIndent 0
    Add-Paragraph -Text '1.3 SYSTEM SPECIFICATION' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 14.5 -FirstLineIndent 0
    Add-Paragraph -Text '1.3.1 HARDWARE SPECIFICATION' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-ParagraphBlock -Lines @(
        'Platform               :   Windows 10 / Windows 11',
        'System                 :   Intel Core i3 or higher',
        'RAM                    :   4 GB or above',
        'Hard Disk              :   250 GB or above',
        'Monitor                :   Standard color monitor',
        'Keyboard               :   Multimedia keyboard',
        'Mouse                  :   Optical mouse'
    ) -Alignment $wdAlignLeft -SpaceAfter 7 -FirstLineIndent 0
    Add-Paragraph -Text '1.3.2 SOFTWARE SPECIFICATION' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-ParagraphBlock -Lines @(
        'Front End              :   HTML5, CSS3, JavaScript, Bootstrap',
        'Back End               :   PHP',
        'Database               :   MySQL / MariaDB',
        'Web Server             :   Apache (XAMPP)',
        'IDE / Editor           :   Visual Studio Code',
        'Web Browser            :   Google Chrome, Microsoft Edge, Mozilla Firefox'
    ) -Alignment $wdAlignLeft -SpaceAfter 7 -FirstLineIndent 0

    Add-PageBreak

    Add-Paragraph -Text '4' -FontName 'Calibri' -FontSize 11 -Bold $true -Alignment $wdAlignCenter -SpaceAfter 14.5 -FirstLineIndent 0
    Add-Paragraph -Text '1.4 ABOUT THE SOFTWARE' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 14.5 -FirstLineIndent 0
    Add-Paragraph -Text '1.4.1 FRONT END' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'HTML5' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'HTML5 is used to create the structure of the portal pages such as the landing page, login page, employee forms, attendance pages, leave forms, loan forms, and payroll pages. Semantic markup helps organize the interface clearly and supports maintainability.' -SpaceAfter 14.35
    Add-Paragraph -Text 'The system uses structured forms, headings, navigation bars, tables, and dashboard cards so that every role can work with the application comfortably. HTML elements also help browser compatibility and improve the overall readability of the interface.' -SpaceAfter 14.35
    Add-Paragraph -Text 'Features of HTML5' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 6 -FirstLineIndent 0
    Add-ParagraphBlock -Lines @(
        '* Provides clear semantic structure for web pages',
        '* Supports responsive layout foundations',
        '* Works across modern browsers',
        '* Integrates easily with CSS and JavaScript'
    ) -Alignment $wdAlignLeft -SpaceAfter 4 -FirstLineIndent 0
    Add-Paragraph -Text 'CSS3' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'CSS3 is used to control the look and feel of the portal. It manages colors, spacing, typography, dashboard cards, tables, navigation, and responsive layout behavior. Both Bootstrap styles and custom theme files are used to create a neat interface for administrators and employees.' -SpaceAfter 14.35
    Add-Paragraph -Text 'The project also uses theme switching and mobile-friendly design patterns so that the application remains usable across desktop and smaller screens. CSS improves consistency and makes the portal easier to understand at a glance.' -SpaceAfter 14.35

    Add-PageBreak

    Add-Paragraph -Text '5' -FontName 'Calibri' -FontSize 11 -Bold $true -Alignment $wdAlignCenter -SpaceAfter 14.5 -FirstLineIndent 0
    Add-Paragraph -Text 'JavaScript' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'JavaScript is used to improve the interactivity of the application. It supports client-side validation, payroll preview calculations, theme toggling, and other user interface actions that provide faster feedback without requiring unnecessary page reloads.' -SpaceAfter 14.35
    Add-Paragraph -Text 'The use of JavaScript in forms reduces incorrect submissions and improves user confidence while entering information. It also supports a smoother experience for HR and finance users who work with repeated data-entry operations.' -SpaceAfter 14.35
    Add-Paragraph -Text 'Features of JavaScript' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 6 -FirstLineIndent 0
    Add-ParagraphBlock -Lines @(
        '* Enables client-side validation and interaction',
        '* Improves response speed for selected actions',
        '* Works together with HTML and CSS in all major browsers',
        '* Supports dynamic behavior in forms and dashboards'
    ) -Alignment $wdAlignLeft -SpaceAfter 4 -FirstLineIndent 0
    Add-Paragraph -Text 'Bootstrap' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'Bootstrap is used to simplify layout management, form alignment, responsive grid behavior, and reusable UI components such as buttons, cards, navbars, and tables. It helps maintain a clean and professional appearance with reduced development effort.' -SpaceAfter 14.35

    Add-PageBreak

    Add-Paragraph -Text '6' -FontName 'Calibri' -FontSize 11 -Bold $true -Alignment $wdAlignCenter -SpaceAfter 14.5 -FirstLineIndent 0
    Add-Paragraph -Text '1.4.2 BACK END' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'PHP' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'PHP is used as the core backend language of the system. It processes form submissions, handles authentication, manages sessions, executes business rules, communicates with the database, and prepares the final HTML views for each role in the portal.' -SpaceAfter 14.35
    Add-Paragraph -Text 'The application follows a controller and model-based organization in which modules such as employees, attendance, leaves, payroll, loans, repayments, performance, and messages are handled through dedicated PHP classes. This structure improves modularity and makes maintenance easier.' -SpaceAfter 14.35
    Add-Paragraph -Text 'PHP also supports secure password verification, role checking, redirects, CSV export, and dynamic rendering of reports. It acts as the connection layer between user interface actions and stored organizational data.' -SpaceAfter 14.35
    Add-Paragraph -Text 'Features of PHP' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 6 -FirstLineIndent 0
    Add-ParagraphBlock -Lines @(
        '* Supports server-side processing and session management',
        '* Integrates easily with MySQL databases',
        '* Suitable for modular web application development',
        '* Simplifies report generation and form handling'
    ) -Alignment $wdAlignLeft -SpaceAfter 4 -FirstLineIndent 0

    Add-PageBreak

    Add-Paragraph -Text '7' -FontName 'Calibri' -FontSize 11 -Bold $true -Alignment $wdAlignCenter -SpaceAfter 14.5 -FirstLineIndent 0
    Add-Paragraph -Text 'MySQL / MariaDB' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'MySQL is used as the relational database for storing user accounts, employee profiles, attendance, leave requests, loan records, repayment entries, payroll details, performance evaluations, membership data, internal messages, notifications, and role mappings. It provides structured, secure, and reusable storage for all core transactions.' -SpaceAfter 14.35
    Add-Paragraph -Text 'The database design uses primary keys and foreign keys to preserve entity relationships. It also uses enumerated fields and unique constraints to improve consistency. Centralized storage helps the organization retrieve reports quickly and maintain long-term records without duplication.' -SpaceAfter 14.35
    Add-Paragraph -Text 'Apache / XAMPP' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'The system is intended to run through Apache in the XAMPP environment. This setup makes local deployment practical for development, testing, demonstration, and intranet use. Apache serves the PHP application while MySQL stores the operational data in the same environment.' -SpaceAfter 14.35

    Add-PageBreak

    Add-Paragraph -Text '8' -FontName 'Calibri' -FontSize 11 -Bold $true -Alignment $wdAlignCenter -SpaceAfter 14.5 -FirstLineIndent 0
    Add-Paragraph -Text 'SYSTEM HIGHLIGHTS' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'The portal supports multi-role operation, secure login, payroll-linked loan deduction, report export, notification alerts, and internal messaging. These features help the organization maintain both employee service and financial recovery activities in one coordinated flow.' -SpaceAfter 14.35
    Add-Paragraph -Text 'The application also provides a modern landing page and dashboard environment that makes the system approachable during demonstrations and project evaluation. Because the modules are separated into controllers, models, and views, the software can be extended in the future without redesigning the full structure.' -SpaceAfter 14.35

    Add-SectionBreak
    $sections.AnalysisTitle = $doc.Sections.Count
    Disable-SectionPageNumbers -SectionIndex $sections.AnalysisTitle
    Add-ChapterTitlePage -Title 'SYSTEM ANALYSIS'

    Add-SectionBreak
    $sections.AnalysisContent = $doc.Sections.Count
    Enable-SectionPageNumbers -SectionIndex $sections.AnalysisContent -StartingNumber 9
    Log-Step 'System analysis section started.'

    Add-Paragraph -Text '2. SYSTEM ANALYSIS' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text '2.1 EXISTING SYSTEM' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'In the existing environment, employee and loan-related information is usually maintained through notebooks, spreadsheets, and repeated manual communication. Attendance, leave, salary deductions, loan requests, and repayment follow-up often depend on separate records handled by different departments. As a result, information is scattered and routine checking takes more time than necessary.' -SpaceAfter 14.35
    Add-Paragraph -Text 'Manual verification becomes a major issue when reports have to be generated for management. HR and finance staff must compare multiple records before they can finalize payroll or confirm a repayment position. Communication delays also arise because employees do not always receive timely updates regarding their leave status, loan application progress, or repayment obligations.' -SpaceAfter 14.35
    Add-Paragraph -Text '2.1.1 DISADVANTAGES OF EXISTING SYSTEM' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-ParagraphBlock -Lines @(
        '* Maintenance of employee and attendance records is time consuming.',
        '* Searching for employee and repayment details is difficult when data is scattered.',
        '* Reports are prepared manually and are prone to delay and error.',
        '* Communication between employees and administrators is not well organized.',
        '* Security is weak when records are maintained in physical files or open spreadsheets.',
        '* Loan tracking and repayment monitoring are not available in real time.'
    ) -Alignment $wdAlignLeft -SpaceAfter 5 -FirstLineIndent 0
    Add-Paragraph -Text '2.2 PROPOSED SYSTEM' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'The proposed system is a dynamic web portal that combines HR operations and employee credit repayment management in one integrated application. Input screens are designed to be clear and role-specific, and records are stored in a centralized MySQL database. This reduces duplication and improves accuracy across departments.' -SpaceAfter 14.35
    Add-Paragraph -Text 'The portal offers login-based access, centralized employee data, online leave and attendance management, payroll generation, loan approval tracking, repayment status updates, messages, and notifications. The result is a more transparent and efficient workflow for both staff members and administrators.' -SpaceAfter 14.35
    Add-Paragraph -Text '2.2.1 ADVANTAGES OF PROPOSED SYSTEM' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-ParagraphBlock -Lines @(
        '* Centralized access to employee, loan, and repayment information.',
        '* Faster communication between employee, HR, and finance users.',
        '* Secure authentication and role-based authorization.',
        '* Automatic report generation and easier record retrieval.',
        '* Reduced paperwork and lower chance of data loss.',
        '* Better tracking of loan status, due amounts, and payroll deductions.'
    ) -Alignment $wdAlignLeft -SpaceAfter 5 -FirstLineIndent 0

    Add-PageBreak

    Add-Paragraph -Text '10' -FontName 'Calibri' -FontSize 11 -Bold $true -Alignment $wdAlignCenter -SpaceAfter 14.5 -FirstLineIndent 0
    Add-Paragraph -Text 'ORGANIZATION STUDY' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'Organization visited: Sri Thirumurugan Autofinance.' -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'The purpose of the system study was to understand how employee records, loan applications, repayment entries, and administrative communication are handled in practice. During the study, discussions focused on manual handling of employee data, delay in repayment follow-up, difficulty in preparing reports, and the need for a more transparent workflow between HR and finance teams.' -SpaceAfter 14.35
    Add-Paragraph -Text 'The study highlighted several expectations from a new system: centralized employee and repayment data, better monitoring of loan status, secure access for different users, quick generation of reports, and a user-friendly environment where employees can submit or track requests without depending on repeated in-person follow-up.' -SpaceAfter 14.35
    Add-Paragraph -Text 'The proposed portal directly addresses these needs by providing structured forms, a common database, internal messaging, notification alerts, loan and repayment status tracking, and exportable reports. This makes the system suitable as a practical replacement for fragmented manual processes.' -SpaceAfter 14.35

    Add-SectionBreak
    $sections.DesignTitle = $doc.Sections.Count
    Disable-SectionPageNumbers -SectionIndex $sections.DesignTitle
    Add-ChapterTitlePage -Title 'SYSTEM DESIGN'

    Add-SectionBreak
    $sections.DesignContent = $doc.Sections.Count
    Enable-SectionPageNumbers -SectionIndex $sections.DesignContent -StartingNumber 11
    Log-Step 'System design section started.'

    Add-Paragraph -Text '3. SYSTEM DESIGN' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text '3.1 DESIGN NOTATION' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text '3.1.1 DATA FLOW DIAGRAM' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'LEVEL 0:' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Image -Path $dfdImagePath -Width 451.3 -Caption 'Data Flow Diagram'

    Add-PageBreak

    Add-Paragraph -Text '12' -FontName 'Calibri' -FontSize 11 -Bold $true -Alignment $wdAlignCenter -SpaceAfter 14.5 -FirstLineIndent 0
    Add-Paragraph -Text 'LEVEL 1:' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'At Level 1, the overall portal is decomposed into operational modules such as authentication, employee management, attendance and leave processing, payroll generation, loan handling, repayment tracking, communication, and reporting. Each process receives structured input from users and stores validated results in the centralized database.' -SpaceAfter 14.35
    Add-Paragraph -Text 'The administrator and HR users provide employee-related input, while finance users manage loan sanction and repayment entries. Employees interact with the system through login, requests, status viewing, messages, and notification-based follow-up. Outputs are displayed as dashboard summaries, lists, reports, and payslip views.' -SpaceAfter 14.35

    Add-PageBreak

    Add-Paragraph -Text '13' -FontName 'Calibri' -FontSize 11 -Bold $true -Alignment $wdAlignCenter -SpaceAfter 14.5 -FirstLineIndent 0
    Add-Paragraph -Text 'LEVEL 2:' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'At Level 2, the detailed process flow covers validation, storage, status change, and notification generation within each module. Attendance records update payroll-related calculations, leave approvals affect salary deductions, loan approvals create repayment expectations, and repayment entries update the finance monitoring view. Messages and notifications act as communication outputs for user actions that require awareness or approval.' -SpaceAfter 14.35
    Add-Paragraph -Text 'This decomposition helps separate responsibilities clearly and ensures that the system can be maintained or extended without affecting unrelated modules. The use of dedicated controllers and models in the application supports this modular design approach.' -SpaceAfter 14.35

    Add-PageBreak

    Add-Paragraph -Text '14' -FontName 'Calibri' -FontSize 11 -Bold $true -Alignment $wdAlignCenter -SpaceAfter 14.5 -FirstLineIndent 0
    Add-Paragraph -Text '3.1.2 ENTITY RELATIONSHIP DIAGRAM' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Image -Path $erImagePath -Width 451.3 -Caption 'Entity Relationship Diagram'

    Add-PageBreak

    Add-Paragraph -Text '15' -FontName 'Calibri' -FontSize 11 -Bold $true -Alignment $wdAlignCenter -SpaceAfter 14.5 -FirstLineIndent 0
    Add-Paragraph -Text '3.2 DESIGN PROCESS' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text '3.2.1 INPUT DESIGN' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'Input design is one of the most important phases in the development of a computerized information system because output quality depends on the accuracy of the entered data. The proposed portal uses clear forms, validation messages, dropdowns, date fields, and protected actions to reduce entry errors and guide users according to their role.' -SpaceAfter 14.35
    Add-Paragraph -Text 'The main input pages in the application include secure login, employee registration and update forms, attendance entry, leave request forms, payroll generation forms, performance evaluation forms, membership forms, loan request and approval forms, repayment entry pages, and internal message creation pages.' -SpaceAfter 14.35
    Add-Paragraph -Text 'Each of these pages has a direct operational purpose: employee records capture personal and employment details, attendance forms support daily timing records, payroll forms calculate deductions, loan forms store credit decisions, and repayment forms update collection progress. Proper validation ensures that incomplete or invalid data cannot be stored accidentally.' -SpaceAfter 14.35

    Add-PageBreak

    Add-Paragraph -Text '16' -FontName 'Calibri' -FontSize 11 -Bold $true -Alignment $wdAlignCenter -SpaceAfter 14.5 -FirstLineIndent 0
    Add-Paragraph -Text '3.2.2 DATABASE DESIGN' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'Database design is used to organize the information of the portal in a structured and reusable form. The project uses MySQL / MariaDB as the backend database. The data is divided into separate tables for users, roles, employees, attendance, leave requests, loans, repayments, payroll, performance, membership, messages, and notifications.' -SpaceAfter 14.35
    Add-Paragraph -Text 'Primary keys uniquely identify records, while foreign keys preserve relationships across modules. For example, employee records are linked to user accounts, attendance records are linked to employees, loans belong to employees, and repayment records belong to loans. This normalized structure reduces redundancy and supports faster retrieval during reporting and monitoring.' -SpaceAfter 14.35
    Add-Paragraph -Text 'The database design also includes status fields and timestamps so that the system can track active records, approval states, and operational history. This makes the portal suitable for both routine daily use and future expansion.' -SpaceAfter 14.35

    Add-PageBreak

    Add-Paragraph -Text '17' -FontName 'Calibri' -FontSize 11 -Bold $true -Alignment $wdAlignCenter -SpaceAfter 14.5 -FirstLineIndent 0
    Add-Paragraph -Text '3.2.3 TABLE DESIGN' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-TableDesign -TableNumber $tableDesigns[0].Number -TableName $tableDesigns[0].Name -PrimaryKey $tableDesigns[0].PrimaryKey -ForeignKey $tableDesigns[0].ForeignKey -Fields $tableDesigns[0].Fields
    Add-TableDesign -TableNumber $tableDesigns[1].Number -TableName $tableDesigns[1].Name -PrimaryKey $tableDesigns[1].PrimaryKey -ForeignKey $tableDesigns[1].ForeignKey -Fields $tableDesigns[1].Fields

    Add-PageBreak

    Add-Paragraph -Text '18' -FontName 'Calibri' -FontSize 11 -Bold $true -Alignment $wdAlignCenter -SpaceAfter 14.5 -FirstLineIndent 0
    Add-TableDesign -TableNumber $tableDesigns[2].Number -TableName $tableDesigns[2].Name -PrimaryKey $tableDesigns[2].PrimaryKey -ForeignKey $tableDesigns[2].ForeignKey -Fields $tableDesigns[2].Fields
    Add-TableDesign -TableNumber $tableDesigns[3].Number -TableName $tableDesigns[3].Name -PrimaryKey $tableDesigns[3].PrimaryKey -ForeignKey $tableDesigns[3].ForeignKey -Fields $tableDesigns[3].Fields

    Add-PageBreak

    Add-Paragraph -Text '19' -FontName 'Calibri' -FontSize 11 -Bold $true -Alignment $wdAlignCenter -SpaceAfter 14.5 -FirstLineIndent 0
    Add-TableDesign -TableNumber $tableDesigns[4].Number -TableName $tableDesigns[4].Name -PrimaryKey $tableDesigns[4].PrimaryKey -ForeignKey $tableDesigns[4].ForeignKey -Fields $tableDesigns[4].Fields
    Add-TableDesign -TableNumber $tableDesigns[5].Number -TableName $tableDesigns[5].Name -PrimaryKey $tableDesigns[5].PrimaryKey -ForeignKey $tableDesigns[5].ForeignKey -Fields $tableDesigns[5].Fields

    Add-PageBreak

    Add-Paragraph -Text '20' -FontName 'Calibri' -FontSize 11 -Bold $true -Alignment $wdAlignCenter -SpaceAfter 14.5 -FirstLineIndent 0
    Add-Paragraph -Text '3.2.4 OUTPUT DESIGN' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'Output design refers to the way processed information is presented to users. In this portal, outputs are role-based and easy to interpret. Administrators and managers see summary cards, process lists, approval states, status badges, report counts, and export links, while employees can view their own attendance, leave status, loan records, repayment updates, messages, notifications, and payslips.' -SpaceAfter 14.35
    Add-ParagraphBlock -Lines @(
        '* Landing Page: Presents the project overview and core feature blocks.',
        '* Login Page: Provides secure entry to the role-based portal.',
        '* Dashboard Pages: Show operational summary values for each user role.',
        '* Employee and Attendance Views: Display employee and daily attendance records.',
        '* Leave, Loan, and Repayment Views: Present request status and finance follow-up data.',
        '* Payroll and Payslip Views: Display deductions, net salary, and payment details.',
        '* Reports Page: Exports attendance, payroll, loan, and repayment data to CSV files.'
    ) -Alignment $wdAlignLeft -SpaceAfter 5 -FirstLineIndent 0

    Add-SectionBreak
    $sections.TestingTitle = $doc.Sections.Count
    Disable-SectionPageNumbers -SectionIndex $sections.TestingTitle
    Add-ChapterTitlePage -Title 'SYSTEM TESTING AND IMPLEMENTATION'

    Add-SectionBreak
    $sections.TestingContent = $doc.Sections.Count
    Enable-SectionPageNumbers -SectionIndex $sections.TestingContent -StartingNumber 21
    Log-Step 'Testing and implementation section started.'

    Add-Paragraph -Text '4. SYSTEM TESTING AND IMPLEMENTATION' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text '4.1 SYSTEM TESTING' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'Testing is a necessary phase in software development because it verifies that the implemented modules satisfy user requirements and behave correctly under practical conditions. In this project, testing focuses on functional correctness, role-based access, validation accuracy, database integrity, and consistency of payroll and repayment calculations.' -SpaceAfter 14.35
    Add-Paragraph -Text 'UNIT TESTING' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'Each major module such as employees, attendance, leave, payroll, loans, repayments, performance, and messaging was tested independently so that input, processing, and output could be verified in isolation. Separate testing of modules helped detect validation and data-handling issues before integration.' -SpaceAfter 14.35
    Add-Paragraph -Text 'VALIDATION TESTING' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'Validation testing ensures that only acceptable values are entered into the system. Email fields, dates, required identifiers, salary values, and status choices are validated before records are saved. Password mismatch, invalid login, duplicate email, and incomplete form submission cases are handled carefully to prevent incorrect data entry.' -SpaceAfter 14.35

    Add-PageBreak

    Add-Paragraph -Text '22' -FontName 'Calibri' -FontSize 11 -Bold $true -Alignment $wdAlignCenter -SpaceAfter 14.5 -FirstLineIndent 0
    Add-Paragraph -Text 'INTEGRATION TESTING' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'Integration testing was performed to verify that the modules work properly when connected together. Examples include attendance updates affecting payroll preview values, loan records being reflected in repayment lists, leave actions triggering notifications, and employee data being reused across payroll, performance, and membership screens.' -SpaceAfter 14.35
    Add-Paragraph -Text 'SYSTEM MAINTENANCE' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'Maintenance is important for any live information system because business rules, users, and organizational policies change over time. The modular controller-model-view structure used in this project supports future enhancement, error correction, and adaptation to new operational requirements. Backups of the database and application files are also essential for safe long-term use.' -SpaceAfter 14.35

    Add-PageBreak

    Add-Paragraph -Text '23' -FontName 'Calibri' -FontSize 11 -Bold $true -Alignment $wdAlignCenter -SpaceAfter 14.5 -FirstLineIndent 0
    Add-Paragraph -Text '4.2 IMPLEMENTATION' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'The system is implemented in a local XAMPP environment using Apache, PHP, and MySQL. User authentication, role checking, and session handling are configured so that only authorized users can access the corresponding modules. After implementation, the portal supports day-to-day employee administration, payroll preparation, loan processing, repayment follow-up, and communication through one consistent interface.' -SpaceAfter 14.35
    Add-Paragraph -Text 'The implemented solution fulfills the key project objectives by reducing manual work, improving data security, and ensuring that operational information is available quickly to the correct users. The application is simple enough for demonstration and academic evaluation while still representing a realistic organizational workflow.' -SpaceAfter 14.35

    Add-SectionBreak
    $sections.ConclusionTitle = $doc.Sections.Count
    Disable-SectionPageNumbers -SectionIndex $sections.ConclusionTitle
    Add-ChapterTitlePage -Title 'CONCLUSION'

    Add-SectionBreak
    $sections.ConclusionContent = $doc.Sections.Count
    Enable-SectionPageNumbers -SectionIndex $sections.ConclusionContent -StartingNumber 24
    Log-Step 'Conclusion section started.'

    Add-Paragraph -Text '5. CONCLUSION AND FUTURE ENHANCEMENT' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text '5.1 CONCLUSION' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'The Dynamic Interaction Web Portal for Human Resource and Employee Credit Repay Process has been developed as an integrated application for employee administration and finance-related recovery processes. The system centralizes attendance, leave, payroll, loans, repayments, messages, notifications, and evaluation data so that organizational work can be carried out with better accuracy and visibility.' -SpaceAfter 14.35
    Add-Paragraph -Text 'The use of PHP, MySQL, Bootstrap, and modular application design has made the software practical, secure, and extendable. The project demonstrates how routine office work can be transformed from manual records into structured digital operations that are easier to manage and report.' -SpaceAfter 14.35
    Add-Paragraph -Text '5.2 FUTURE ENHANCEMENT' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'The current portal provides the essential features required for employee and repayment management. In the future, the system can be enhanced with mobile access, stronger analytics dashboards, reminder automation for overdue repayment, richer approval workflows, and downloadable PDF reports for more modules.' -SpaceAfter 14.35
    Add-Paragraph -Text 'Future development may also include SMS or WhatsApp alerts, biometric attendance integration, document attachment support for loans, online payment gateway integration, advanced audit trails, and multi-branch administration support.' -SpaceAfter 14.35

    Add-PageBreak

    Add-Paragraph -Text '25' -FontName 'Calibri' -FontSize 11 -Bold $true -Alignment $wdAlignCenter -SpaceAfter 14.5 -FirstLineIndent 0
    Add-Paragraph -Text 'With these enhancements, the portal can evolve from an academic project into a more comprehensive enterprise tool for finance-oriented organizations. The present implementation establishes a strong foundation for that future growth by organizing the key operational flows in a secure and understandable manner.' -SpaceAfter 14.35

    Add-SectionBreak
    $sections.BibliographyTitle = $doc.Sections.Count
    Disable-SectionPageNumbers -SectionIndex $sections.BibliographyTitle
    Add-ChapterTitlePage -Title 'BIBLIOGRAPHY'

    Add-SectionBreak
    $sections.BibliographyContent = $doc.Sections.Count
    Enable-SectionPageNumbers -SectionIndex $sections.BibliographyContent -StartingNumber 26
    Log-Step 'Bibliography section started.'

    Add-Paragraph -Text '6. BIBLIOGRAPHY' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-Paragraph -Text 'REFERENCE BOOKS' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-ParagraphBlock -Lines @(
        '* Ian Sommerville, Software Engineering.',
        '* Roger S. Pressman, Software Engineering: A Practitioner''s Approach.',
        '* Luke Welling and Laura Thomson, PHP and MySQL Web Development.',
        '* Jeffrey A. Hoffer, Joey F. George and Joseph S. Valacich, Modern Systems Analysis and Design.'
    ) -Alignment $wdAlignLeft -SpaceAfter 5 -FirstLineIndent 0
    Add-Paragraph -Text 'WEBSITES' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    Add-ParagraphBlock -Lines @(
        '* https://www.php.net',
        '* https://dev.mysql.com/doc',
        '* https://developer.mozilla.org',
        '* https://getbootstrap.com/docs/5.3',
        '* https://www.w3schools.com'
    ) -Alignment $wdAlignLeft -SpaceAfter 5 -FirstLineIndent 0

    Add-SectionBreak
    $sections.AppendixContent = $doc.Sections.Count
    Enable-SectionPageNumbers -SectionIndex $sections.AppendixContent -StartingNumber 27
    Log-Step 'Appendix section started.'

    Add-Paragraph -Text 'APPENDIX - SAMPLE SCREENS' -Bold $true -Alignment $wdAlignLeft -SpaceAfter 8 -FirstLineIndent 0
    if ($appendixImages.Count -eq 0) {
        Add-Paragraph -Text 'Sample screens were not available at build time.' -SpaceAfter 14.35
    } else {
        for ($i = 0; $i -lt $appendixImages.Count; $i++) {
            Add-Image -Path $appendixImages[$i].Path -Width 451.3 -Caption $appendixImages[$i].Caption
            if ($i -lt ($appendixImages.Count - 1)) {
                Add-PageBreak
            }
        }
    }

    Log-Step 'Updating fields.'
    $doc.Fields.Update() | Out-Null
    Log-Step 'Saving DOCX.'
    $doc.SaveAs2($outputPath)
    Log-Step 'DOCX saved.'
    try {
        Log-Step 'Exporting PDF.'
        $doc.ExportAsFixedFormat($pdfPath, $wdExportFormatPDF)
        Log-Step 'PDF exported.'
    } catch {
        Log-Step "PDF export skipped: $($_.Exception.Message)"
    }
}
finally {
    if ($doc) {
        $doc.Close([ref]$true)
        [System.Runtime.Interopservices.Marshal]::ReleaseComObject($doc) | Out-Null
    }
    if ($word) {
        $word.Quit()
        [System.Runtime.Interopservices.Marshal]::ReleaseComObject($word) | Out-Null
    }
    [GC]::Collect()
    [GC]::WaitForPendingFinalizers()
}
