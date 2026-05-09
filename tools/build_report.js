const fs = require("fs");
const path = require("path");
const { imageSize } = require("image-size");
const {
    AlignmentType,
    BorderStyle,
    Document,
    Footer,
    ImageRun,
    LineRuleType,
    Packer,
    PageBreak,
    PageNumber,
    Paragraph,
    SectionType,
    Table,
    TableCell,
    TableRow,
    TextRun,
    WidthType,
    convertInchesToTwip,
} = require("docx");

const root = path.resolve(__dirname, "..");
const outputPath = path.join(root, "HR Project Fair Copy.docx");

const pageSize = {
    width: 11906,
    height: 16838,
};

const margin = {
    top: convertInchesToTwip(1),
    right: convertInchesToTwip(1),
    bottom: convertInchesToTwip(1),
    left: convertInchesToTwip(1),
    header: convertInchesToTwip(0.5),
    footer: convertInchesToTwip(0.5),
};

const images = {
    dfd: path.join(root, "ocr", "design", "page-4.png"),
    er: path.join(root, "ocr", "design", "page-5.png"),
    landing: path.join(root, "screens", "landing.png"),
    login: path.join(root, "screens", "login.png"),
};

const tableDesigns = [
    {
        number: "1",
        name: "User Details",
        primaryKey: "id",
        foreignKey: "",
        fields: [
            ["id", "int", "10", "Unique user identification number"],
            ["username", "varchar", "100", "Username used for authentication"],
            ["email", "varchar", "150", "Official email address"],
            ["password_hash", "varchar", "255", "Encrypted password value"],
            ["full_name", "varchar", "150", "Full name of the user"],
            ["status", "tinyint", "1", "Account active or inactive"],
            ["created_at", "timestamp", "-", "Record creation date and time"],
        ],
    },
    {
        number: "2",
        name: "Employee Details",
        primaryKey: "id",
        foreignKey: "user_id",
        fields: [
            ["id", "int", "11", "Unique employee identification number"],
            ["user_id", "int", "10", "Linked login account"],
            ["employee_code", "varchar", "50", "Employee reference code"],
            ["first_name", "varchar", "100", "Employee first name"],
            ["last_name", "varchar", "100", "Employee last name"],
            ["department", "varchar", "200", "Assigned department"],
            ["designation", "varchar", "100", "Job role or designation"],
            ["date_of_joining", "date", "-", "Employee joining date"],
            ["salary", "decimal", "10,2", "Basic salary information"],
        ],
    },
    {
        number: "3",
        name: "Attendance Details",
        primaryKey: "id",
        foreignKey: "employee_id",
        fields: [
            ["id", "int", "11", "Attendance identification number"],
            ["employee_id", "int", "11", "Linked employee record"],
            ["attendance_date", "date", "-", "Attendance date"],
            ["check_in", "time", "-", "Punch-in time"],
            ["check_out", "time", "-", "Punch-out time"],
            ["status", "enum", "-", "Present, absent, late or half day"],
            ["created_at", "timestamp", "-", "Record creation timestamp"],
        ],
    },
    {
        number: "4",
        name: "Loan Details",
        primaryKey: "id",
        foreignKey: "employee_id, created_by",
        fields: [
            ["id", "int", "10", "Loan identification number"],
            ["employee_id", "int", "11", "Employee who requested the loan"],
            ["requested_amount", "decimal", "12,2", "Requested loan amount"],
            ["approved_amount", "decimal", "12,2", "Sanctioned amount"],
            ["interest_rate", "decimal", "5,2", "Interest percentage"],
            ["tenure_months", "int", "11", "Repayment period in months"],
            ["emi_amount", "decimal", "12,2", "Calculated EMI amount"],
            ["status", "enum", "-", "Pending, approved, rejected or closed"],
        ],
    },
    {
        number: "5",
        name: "Loan Repayment Details",
        primaryKey: "id",
        foreignKey: "loan_id",
        fields: [
            ["id", "int", "10", "Repayment identification number"],
            ["loan_id", "int", "10", "Linked loan record"],
            ["due_date", "date", "-", "Scheduled due date"],
            ["paid_date", "date", "-", "Actual payment date"],
            ["amount_due", "decimal", "12,2", "Amount expected to be paid"],
            ["amount_paid", "decimal", "12,2", "Amount actually received"],
            ["payment_status", "enum", "-", "Pending, paid, overdue or partial"],
            ["payment_mode", "enum", "-", "Cash, bank transfer, UPI or salary deduction"],
        ],
    },
    {
        number: "6",
        name: "Payroll Details",
        primaryKey: "id",
        foreignKey: "employee_id",
        fields: [
            ["id", "int", "11", "Payroll identification number"],
            ["employee_id", "int", "11", "Linked employee record"],
            ["salary_month", "varchar", "20", "Payroll month"],
            ["basic_salary", "decimal", "10,2", "Basic salary"],
            ["allowances", "decimal", "10,2", "Allowance amount"],
            ["bonus", "decimal", "10,2", "Bonus value"],
            ["leave_deduction", "decimal", "10,2", "Deduction for leave"],
            ["loan_emi_deduction", "decimal", "10,2", "Loan EMI deduction"],
            ["net_salary", "decimal", "10,2", "Net salary payable"],
        ],
    },
];

const run = (text, options = {}) =>
    new TextRun({
        text,
        font: options.font || "Times New Roman",
        size: options.size || 26,
        bold: Boolean(options.bold),
    });

const para = (text = "", options = {}) =>
    new Paragraph({
        alignment: options.alignment || AlignmentType.JUSTIFIED,
        spacing: {
            before: options.before || 0,
            after: options.after !== undefined ? options.after : 287,
            line: options.line || 276,
            lineRule: options.lineRule || LineRuleType.AUTO,
        },
        indent: {
            firstLine: options.firstLine !== undefined ? options.firstLine : 720,
            left: options.left || 0,
            hanging: options.hanging || 0,
        },
        pageBreakBefore: Boolean(options.pageBreakBefore),
        children: options.children || [run(text, options)],
    });

const blankParagraph = () =>
    new Paragraph({
        spacing: { after: 0, line: 240, lineRule: LineRuleType.AUTO },
    });

const blankLines = (count) => Array.from({ length: count }, () => blankParagraph());

const pageBreak = () => new Paragraph({ children: [new PageBreak()] });

const footer = () =>
    new Footer({
        children: [
            new Paragraph({
                alignment: AlignmentType.CENTER,
                children: [new TextRun({ children: [PageNumber.CURRENT], size: 22, font: "Calibri" })],
            }),
        ],
    });

const sectionProps = (pageStart) => ({
    type: SectionType.NEXT_PAGE,
    page: {
        size: pageSize,
        margin,
        pageNumbers: pageStart ? { start: pageStart } : undefined,
    },
});

const imageBlock = (filePath, caption) => {
    if (!fs.existsSync(filePath)) {
        return [];
    }

    const data = fs.readFileSync(filePath);
    const dimensions = imageSize(data);
    const maxWidth = 450;
    const maxHeight = 580;
    const scale = Math.min(maxWidth / dimensions.width, maxHeight / dimensions.height, 1);

    return [
        new Paragraph({
            alignment: AlignmentType.CENTER,
            spacing: { after: 120, line: 240, lineRule: LineRuleType.AUTO },
            children: [
                new ImageRun({
                    type: path.extname(filePath).replace(".", "").toLowerCase(),
                    data,
                    transformation: {
                        width: Math.round(dimensions.width * scale),
                        height: Math.round(dimensions.height * scale),
                    },
                }),
            ],
        }),
        para(caption, {
            alignment: AlignmentType.CENTER,
            size: 22,
            font: "Times New Roman",
            firstLine: 0,
            after: 120,
        }),
    ];
};

const tableCell = (text, bold = false) =>
    new TableCell({
        children: [
            para(text, {
                alignment: AlignmentType.LEFT,
                size: 20,
                bold,
                firstLine: 0,
                after: 80,
                line: 240,
            }),
        ],
    });

const tableDesignBlock = (entry) => [
    para(`${entry.number}. Table Name: ${entry.name}`, {
        alignment: AlignmentType.LEFT,
        bold: true,
        firstLine: 0,
        after: 120,
    }),
    para(`Primary Key: ${entry.primaryKey}`, {
        alignment: AlignmentType.LEFT,
        firstLine: 0,
        after: 80,
    }),
    ...(entry.foreignKey
        ? [
              para(`Foreign Key: ${entry.foreignKey}`, {
                  alignment: AlignmentType.LEFT,
                  firstLine: 0,
                  after: 120,
              }),
          ]
        : []),
    new Table({
        width: { size: 100, type: WidthType.PERCENTAGE },
        columnWidths: [2200, 1700, 1000, 4200],
        borders: {
            top: { style: BorderStyle.SINGLE, size: 1, color: "000000" },
            bottom: { style: BorderStyle.SINGLE, size: 1, color: "000000" },
            left: { style: BorderStyle.SINGLE, size: 1, color: "000000" },
            right: { style: BorderStyle.SINGLE, size: 1, color: "000000" },
            insideHorizontal: { style: BorderStyle.SINGLE, size: 1, color: "000000" },
            insideVertical: { style: BorderStyle.SINGLE, size: 1, color: "000000" },
        },
        rows: [
            new TableRow({
                children: [
                    tableCell("Field Name", true),
                    tableCell("Data Type", true),
                    tableCell("Size", true),
                    tableCell("Description", true),
                ],
            }),
            ...entry.fields.map(
                (field) =>
                    new TableRow({
                        children: [
                            tableCell(field[0]),
                            tableCell(field[1]),
                            tableCell(field[2]),
                            tableCell(field[3]),
                        ],
                    }),
            ),
        ],
    }),
    para("", { firstLine: 0, after: 120 }),
];

const chapterTitleSection = (title) => ({
    properties: sectionProps(),
    children: [...blankLines(16), para(title, { size: 48, bold: true, alignment: AlignmentType.LEFT }), ...blankLines(16)],
});

const tocSection = {
    properties: sectionProps(),
    children: [
        ...blankLines(18),
        para("CONTENT", {
            size: 48,
            bold: true,
            alignment: AlignmentType.CENTER,
            firstLine: 0,
            after: 120,
        }),
        para("CONTENT                                                                                                         Page. No.", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 40 }),
        para("1. INTRODUCTION                                                                                                      1", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("1.1 OVERVIEW OF THE SYSTEM", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("1.2 MODULE DESCRIPTION", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("1.3 SYSTEM SPECIFICATION", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("1.3.1 HARDWARE REQUIREMENTS", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("1.3.2 SOFTWARE REQUIREMENTS", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("1.4 ABOUT THE SOFTWARE", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("1.4.1 FRONT END", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("1.4.2 BACK END", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("2. SYSTEM ANALYSIS                                                                                                  9", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("2.1 EXISTING SYSTEM", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("2.1.1 DISADVANTAGES OF EXISTING SYSTEM", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("2.2 PROPOSED SYSTEM", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("2.2.1 ADVANTAGES OF PROPOSED SYSTEM", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("3. SYSTEM DESIGN                                                                                                   11", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("3.1 DESIGN NOTATION", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("3.1.1 DATA FLOW DIAGRAM", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("3.1.2 ENTITY RELATIONSHIP DIAGRAM", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("3.2 DESIGN PROCESS", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("3.2.1 INPUT DESIGN", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("3.2.2 DATABASE DESIGN", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("3.2.3 TABLE DESIGN", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("3.2.4 OUTPUT DESIGN", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("4. SYSTEM TESTING AND IMPLEMENTATION                                                                      21", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("4.1 SYSTEM TESTING", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("4.2 IMPLEMENTATION", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("5. CONCLUSION AND FUTURE ENHANCEMENT                                                             24", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("6. BIBLIOGRAPHY                                                                                                  26", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("APPENDIX                                                                                                          27   SAMPLE SCREENS", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
    ],
};

const abstractSection = {
    properties: sectionProps(),
    children: [
        ...blankLines(6),
        para("ABSTRACT", { size: 36, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para('The project entitled "Dynamic Interaction Web Portal for Human Resource and Employee Credit Repay Process" is developed to organize employee administration and loan repayment activities inside an organization through a single web-based platform.'),
        para("The application supports employee records, attendance, leave management, payroll generation, employee performance review, membership tracking, loan issue, repayment monitoring, internal communication, and notification handling. The frontend is built using HTML, CSS, JavaScript, Bootstrap, and responsive layouts, while the backend is implemented in PHP with a MySQL database."),
        para("The main objective of the proposed system is to reduce manual work in the human resource and finance departments by integrating all employee-related and credit-recovery processes into one secure portal. The system provides role-based access for administrators, HR managers, finance managers, and employees so that each user can access only the functions related to their work."),
        para("By computerizing loan applications, approval flow, repayment schedules, payroll-linked deductions, and communication between employees and administrators, the project improves transparency, saves time, and increases data accuracy. The system also helps the organization generate reports quickly and maintain secure, centralized records for future reference."),
    ],
};

const introSection = {
    properties: sectionProps(1),
    footers: { default: footer() },
    children: [
        para("1. INTRODUCTION", { bold: true }),
        para("1.1 OVERVIEW OF THE SYSTEM", { size: 24, bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("In modern organizations, human resource administration and employee credit recovery activities require continuous coordination between HR teams, finance teams, and employees. When these activities are maintained through registers, spreadsheets, and disconnected communication channels, it becomes difficult to track attendance, leave, payroll deductions, loan approvals, and repayment status accurately."),
        para("The Dynamic Interaction Web Portal for Human Resource and Employee Credit Repay Process is designed to provide a centralized and interactive environment for these operations. The application combines employee master management, attendance monitoring, leave handling, payroll processing, performance evaluation, membership maintenance, loan processing, repayment control, and internal communication into one secure web portal."),
        para("The system is developed as a role-based application. Administrators supervise the platform, HR managers maintain employee and policy-related activities, finance managers handle loans and repayments, and employees can view or interact with their own records, requests, messages, and notifications. This shared structure improves accountability and reduces delays in decision making."),
        para("The project uses PHP for server-side processing and MySQL for database storage. Bootstrap and custom CSS are used to provide responsive user interfaces, while JavaScript supports validation and interactive actions. The application is intended to run in a local or intranet environment through XAMPP, making deployment practical for medium-scale organizations."),
        para("By automating routine operations and maintaining all records in one database, the system supports fast reporting, improves data consistency, and creates a transparent channel for employee services and credit repayment follow-up."),
        para("MODULES:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 200 }),
        ...[
            "1. Authentication and Role Management Module",
            "2. Employee Information and Attendance Module",
            "3. Leave and Payroll Management Module",
            "4. Loan Application and Repayment Module",
            "5. Performance and Promotion Module",
            "6. Membership and Communication Module",
            "7. Notification and Reporting Module",
        ].map((line) =>
            para(line, {
                bold: true,
                alignment: AlignmentType.LEFT,
                firstLine: 0,
                left: 720,
                after: 100,
                line: 480,
            }),
        ),
        pageBreak(),
        para("2", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("1.2 MODULE DESCRIPTION", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 200 }),
        para("1. Authentication and Role Management Module:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, left: 360, hanging: 360, after: 80 }),
        para("This module manages secure login, session handling, user accounts, and role mapping for administrator, HR manager, finance manager, and employee users. Passwords are stored in encrypted form and access is controlled according to the assigned role.", { firstLine: 360, after: 120 }),
        para("2. Employee Information and Attendance Module:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, left: 360, hanging: 360, after: 80 }),
        para("This module maintains employee identity, department, designation, salary data, and joining information. It also records attendance date, punch-in, punch-out, and status details so that HR and payroll calculations can be performed correctly.", { firstLine: 360, after: 120 }),
        para("3. Leave and Payroll Management Module:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, left: 360, hanging: 360, after: 80 }),
        para("This module handles leave requests, approval decisions, payroll generation, deduction calculation, loan EMI deduction, and payslip creation. It helps the organization maintain salary discipline and accurate monthly settlement records.", { firstLine: 360, after: 120 }),
        para("4. Loan Application and Repayment Module:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, left: 360, hanging: 360, after: 80 }),
        para("This module records employee loan requests, approvals, tenure, EMI amounts, repayment schedules, payment modes, and status history. It supports finance teams in tracking pending, paid, overdue, and partially paid repayments.", { firstLine: 360, after: 120 }),
        para("5. Performance and Promotion Module:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, left: 360, hanging: 360, after: 80 }),
        para("This module stores monthly evaluation data such as test score, attendance score, overall score, grade, promotion recommendation, and promotion approval details. It helps management review employee growth using measurable criteria.", { firstLine: 360, after: 120 }),
        para("6. Membership and Communication Module:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, left: 360, hanging: 360, after: 80 }),
        para("This module captures membership records and provides internal messaging between employees, HR, and finance users. It supports subject-based communication for leave, loan, repayment, attendance, and general updates.", { firstLine: 360, after: 120 }),
        para("7. Notification and Reporting Module:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, left: 360, hanging: 360, after: 80 }),
        para("This module generates notifications for important events such as leave requests and repayment updates, and also exports attendance, payroll, loan, and repayment reports. It improves visibility and speeds up administrative follow-up.", { firstLine: 360, after: 200 }),
        pageBreak(),
        para("3", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("1.3 SYSTEM SPECIFICATION", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 200 }),
        para("1.3.1 HARDWARE SPECIFICATION", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        ...[
            "Platform               :   Windows 10 / Windows 11",
            "System                 :   Intel Core i3 or higher",
            "RAM                    :   4 GB or above",
            "Hard Disk              :   250 GB or above",
            "Monitor                :   Standard color monitor",
            "Keyboard               :   Multimedia keyboard",
            "Mouse                  :   Optical mouse",
        ].map((line) => para(line, { alignment: AlignmentType.LEFT, firstLine: 0, after: 80 })),
        para("1.3.2 SOFTWARE SPECIFICATION", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        ...[
            "Front End              :   HTML5, CSS3, JavaScript, Bootstrap",
            "Back End               :   PHP",
            "Database               :   MySQL / MariaDB",
            "Web Server             :   Apache (XAMPP)",
            "IDE / Editor           :   Visual Studio Code",
            "Web Browser            :   Google Chrome, Microsoft Edge, Mozilla Firefox",
        ].map((line) => para(line, { alignment: AlignmentType.LEFT, firstLine: 0, after: 80 })),
        pageBreak(),
        para("4", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("1.4 ABOUT THE SOFTWARE", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 200 }),
        para("1.4.1 FRONT END", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("HTML5", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("HTML5 is used to create the structure of the portal pages such as the landing page, login page, employee forms, attendance pages, leave forms, loan forms, and payroll pages. Semantic markup helps organize the interface clearly and supports maintainability."),
        para("The system uses structured forms, headings, navigation bars, tables, and dashboard cards so that every role can work with the application comfortably. HTML elements also help browser compatibility and improve the overall readability of the interface."),
        para("Features of HTML5", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 120 }),
        ...[
            "* Provides clear semantic structure for web pages",
            "* Supports responsive layout foundations",
            "* Works across modern browsers",
            "* Integrates easily with CSS and JavaScript",
        ].map((line) => para(line, { alignment: AlignmentType.LEFT, firstLine: 0, after: 60 })),
        para("CSS3", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("CSS3 is used to control the look and feel of the portal. It manages colors, spacing, typography, dashboard cards, tables, navigation, and responsive layout behavior. Both Bootstrap styles and custom theme files are used to create a neat interface for administrators and employees."),
        para("The project also uses theme switching and mobile-friendly design patterns so that the application remains usable across desktop and smaller screens. CSS improves consistency and makes the portal easier to understand at a glance."),
        pageBreak(),
        para("5", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("JavaScript", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("JavaScript is used to improve the interactivity of the application. It supports client-side validation, payroll preview calculations, theme toggling, and other user interface actions that provide faster feedback without requiring unnecessary page reloads."),
        para("The use of JavaScript in forms reduces incorrect submissions and improves user confidence while entering information. It also supports a smoother experience for HR and finance users who work with repeated data-entry operations."),
        para("Features of JavaScript", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 120 }),
        ...[
            "* Enables client-side validation and interaction",
            "* Improves response speed for selected actions",
            "* Works together with HTML and CSS in all major browsers",
            "* Supports dynamic behavior in forms and dashboards",
        ].map((line) => para(line, { alignment: AlignmentType.LEFT, firstLine: 0, after: 60 })),
        para("Bootstrap", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("Bootstrap is used to simplify layout management, form alignment, responsive grid behavior, and reusable UI components such as buttons, cards, navbars, and tables. It helps maintain a clean and professional appearance with reduced development effort."),
        pageBreak(),
        para("6", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("1.4.2 BACK END", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("PHP", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("PHP is used as the core backend language of the system. It processes form submissions, handles authentication, manages sessions, executes business rules, communicates with the database, and prepares the final HTML views for each role in the portal."),
        para("The application follows a controller and model-based organization in which modules such as employees, attendance, leaves, payroll, loans, repayments, performance, and messages are handled through dedicated PHP classes. This structure improves modularity and makes maintenance easier."),
        para("PHP also supports secure password verification, role checking, redirects, CSV export, and dynamic rendering of reports. It acts as the connection layer between user interface actions and stored organizational data."),
        para("Features of PHP", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 120 }),
        ...[
            "* Supports server-side processing and session management",
            "* Integrates easily with MySQL databases",
            "* Suitable for modular web application development",
            "* Simplifies report generation and form handling",
        ].map((line) => para(line, { alignment: AlignmentType.LEFT, firstLine: 0, after: 60 })),
        pageBreak(),
        para("7", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("MySQL / MariaDB", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("MySQL is used as the relational database for storing user accounts, employee profiles, attendance, leave requests, loan records, repayment entries, payroll details, performance evaluations, membership data, internal messages, notifications, and role mappings. It provides structured, secure, and reusable storage for all core transactions."),
        para("The database design uses primary keys and foreign keys to preserve entity relationships. It also uses enumerated fields and unique constraints to improve consistency. Centralized storage helps the organization retrieve reports quickly and maintain long-term records without duplication."),
        para("Apache / XAMPP", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("The system is intended to run through Apache in the XAMPP environment. This setup makes local deployment practical for development, testing, demonstration, and intranet use. Apache serves the PHP application while MySQL stores the operational data in the same environment."),
        pageBreak(),
        para("8", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("SYSTEM HIGHLIGHTS", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("The portal supports multi-role operation, secure login, payroll-linked loan deduction, report export, notification alerts, and internal messaging. These features help the organization maintain both employee service and financial recovery activities in one coordinated flow."),
        para("The application also provides a modern landing page and dashboard environment that makes the system approachable during demonstrations and project evaluation. Because the modules are separated into controllers, models, and views, the software can be extended in the future without redesigning the full structure."),
    ],
};

const analysisSection = {
    properties: sectionProps(9),
    footers: { default: footer() },
    children: [
        para("2. SYSTEM ANALYSIS", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("2.1 EXISTING SYSTEM", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("In the existing environment, employee and loan-related information is usually maintained through notebooks, spreadsheets, and repeated manual communication. Attendance, leave, salary deductions, loan requests, and repayment follow-up often depend on separate records handled by different departments. As a result, information is scattered and routine checking takes more time than necessary."),
        para("Manual verification becomes a major issue when reports have to be generated for management. HR and finance staff must compare multiple records before they can finalize payroll or confirm a repayment position. Communication delays also arise because employees do not always receive timely updates regarding their leave status, loan application progress, or repayment obligations."),
        para("2.1.1 DISADVANTAGES OF EXISTING SYSTEM", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        ...[
            "* Maintenance of employee and attendance records is time consuming.",
            "* Searching for employee and repayment details is difficult when data is scattered.",
            "* Reports are prepared manually and are prone to delay and error.",
            "* Communication between employees and administrators is not well organized.",
            "* Security is weak when records are maintained in physical files or open spreadsheets.",
            "* Loan tracking and repayment monitoring are not available in real time.",
        ].map((line) => para(line, { alignment: AlignmentType.LEFT, firstLine: 0, after: 60 })),
        para("2.2 PROPOSED SYSTEM", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("The proposed system is a dynamic web portal that combines HR operations and employee credit repayment management in one integrated application. Input screens are designed to be clear and role-specific, and records are stored in a centralized MySQL database. This reduces duplication and improves accuracy across departments."),
        para("The portal offers login-based access, centralized employee data, online leave and attendance management, payroll generation, loan approval tracking, repayment status updates, messages, and notifications. The result is a more transparent and efficient workflow for both staff members and administrators."),
        para("2.2.1 ADVANTAGES OF PROPOSED SYSTEM", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        ...[
            "* Centralized access to employee, loan, and repayment information.",
            "* Faster communication between employee, HR, and finance users.",
            "* Secure authentication and role-based authorization.",
            "* Automatic report generation and easier record retrieval.",
            "* Reduced paperwork and lower chance of data loss.",
            "* Better tracking of loan status, due amounts, and payroll deductions.",
        ].map((line) => para(line, { alignment: AlignmentType.LEFT, firstLine: 0, after: 60 })),
        pageBreak(),
        para("10", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("ORGANIZATION STUDY", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("Organization visited: Sri Thirumurugan Autofinance.", { alignment: AlignmentType.LEFT, firstLine: 0, after: 120 }),
        para("The purpose of the system study was to understand how employee records, loan applications, repayment entries, and administrative communication are handled in practice. During the study, discussions focused on manual handling of employee data, delay in repayment follow-up, difficulty in preparing reports, and the need for a more transparent workflow between HR and finance teams."),
        para("The study highlighted several expectations from a new system: centralized employee and repayment data, better monitoring of loan status, secure access for different users, quick generation of reports, and a user-friendly environment where employees can submit or track requests without depending on repeated in-person follow-up."),
        para("The proposed portal directly addresses these needs by providing structured forms, a common database, internal messaging, notification alerts, loan and repayment status tracking, and exportable reports. This makes the system suitable as a practical replacement for fragmented manual processes."),
    ],
};

const designSection = {
    properties: sectionProps(11),
    footers: { default: footer() },
    children: [
        para("3. SYSTEM DESIGN", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("3.1 DESIGN NOTATION", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("3.1.1 DATA FLOW DIAGRAM", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("LEVEL 0:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        ...imageBlock(images.dfd, "Data Flow Diagram"),
        pageBreak(),
        para("12", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("LEVEL 1:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("At Level 1, the overall portal is decomposed into operational modules such as authentication, employee management, attendance and leave processing, payroll generation, loan handling, repayment tracking, communication, and reporting. Each process receives structured input from users and stores validated results in the centralized database."),
        para("The administrator and HR users provide employee-related input, while finance users manage loan sanction and repayment entries. Employees interact with the system through login, requests, status viewing, messages, and notification-based follow-up. Outputs are displayed as dashboard summaries, lists, reports, and payslip views."),
        pageBreak(),
        para("13", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("LEVEL 2:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("At Level 2, the detailed process flow covers validation, storage, status change, and notification generation within each module. Attendance records update payroll-related calculations, leave approvals affect salary deductions, loan approvals create repayment expectations, and repayment entries update the finance monitoring view. Messages and notifications act as communication outputs for user actions that require awareness or approval."),
        para("This decomposition helps separate responsibilities clearly and ensures that the system can be maintained or extended without affecting unrelated modules. The use of dedicated controllers and models in the application supports this modular design approach."),
        pageBreak(),
        para("14", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("3.1.2 ENTITY RELATIONSHIP DIAGRAM", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        ...imageBlock(images.er, "Entity Relationship Diagram"),
        pageBreak(),
        para("15", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("3.2 DESIGN PROCESS", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("3.2.1 INPUT DESIGN", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("Input design is one of the most important phases in the development of a computerized information system because output quality depends on the accuracy of the entered data. The proposed portal uses clear forms, validation messages, dropdowns, date fields, and protected actions to reduce entry errors and guide users according to their role."),
        para("The main input pages in the application include secure login, employee registration and update forms, attendance entry, leave request forms, payroll generation forms, performance evaluation forms, membership forms, loan request and approval forms, repayment entry pages, and internal message creation pages."),
        para("Each of these pages has a direct operational purpose: employee records capture personal and employment details, attendance forms support daily timing records, payroll forms calculate deductions, loan forms store credit decisions, and repayment forms update collection progress. Proper validation ensures that incomplete or invalid data cannot be stored accidentally."),
        pageBreak(),
        para("16", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("3.2.2 DATABASE DESIGN", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("Database design is used to organize the information of the portal in a structured and reusable form. The project uses MySQL / MariaDB as the backend database. The data is divided into separate tables for users, roles, employees, attendance, leave requests, loans, repayments, payroll, performance, membership, messages, and notifications."),
        para("Primary keys uniquely identify records, while foreign keys preserve relationships across modules. For example, employee records are linked to user accounts, attendance records are linked to employees, loans belong to employees, and repayment records belong to loans. This normalized structure reduces redundancy and supports faster retrieval during reporting and monitoring."),
        para("The database design also includes status fields and timestamps so that the system can track active records, approval states, and operational history. This makes the portal suitable for both routine daily use and future expansion."),
        pageBreak(),
        para("17", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("3.2.3 TABLE DESIGN", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        ...tableDesignBlock(tableDesigns[0]),
        ...tableDesignBlock(tableDesigns[1]),
        pageBreak(),
        para("18", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        ...tableDesignBlock(tableDesigns[2]),
        ...tableDesignBlock(tableDesigns[3]),
        pageBreak(),
        para("19", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        ...tableDesignBlock(tableDesigns[4]),
        ...tableDesignBlock(tableDesigns[5]),
        pageBreak(),
        para("20", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("3.2.4 OUTPUT DESIGN", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("Output design refers to the way processed information is presented to users. In this portal, outputs are role-based and easy to interpret. Administrators and managers see summary cards, process lists, approval states, status badges, report counts, and export links, while employees can view their own attendance, leave status, loan records, repayment updates, messages, notifications, and payslips."),
        ...[
            "* Landing Page: Presents the project overview and core feature blocks.",
            "* Login Page: Provides secure entry to the role-based portal.",
            "* Dashboard Pages: Show operational summary values for each user role.",
            "* Employee and Attendance Views: Display employee and daily attendance records.",
            "* Leave, Loan, and Repayment Views: Present request status and finance follow-up data.",
            "* Payroll and Payslip Views: Display deductions, net salary, and payment details.",
            "* Reports Page: Exports attendance, payroll, loan, and repayment data to CSV files.",
        ].map((line) => para(line, { alignment: AlignmentType.LEFT, firstLine: 0, after: 60 })),
    ],
};

const testingSection = {
    properties: sectionProps(21),
    footers: { default: footer() },
    children: [
        para("4. SYSTEM TESTING AND IMPLEMENTATION", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("4.1 SYSTEM TESTING", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("Testing is a necessary phase in software development because it verifies that the implemented modules satisfy user requirements and behave correctly under practical conditions. In this project, testing focuses on functional correctness, role-based access, validation accuracy, database integrity, and consistency of payroll and repayment calculations."),
        para("UNIT TESTING", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("Each major module such as employees, attendance, leave, payroll, loans, repayments, performance, and messaging was tested independently so that input, processing, and output could be verified in isolation. Separate testing of modules helped detect validation and data-handling issues before integration."),
        para("VALIDATION TESTING", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("Validation testing ensures that only acceptable values are entered into the system. Email fields, dates, required identifiers, salary values, and status choices are validated before records are saved. Password mismatch, invalid login, duplicate email, and incomplete form submission cases are handled carefully to prevent incorrect data entry."),
        pageBreak(),
        para("22", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("INTEGRATION TESTING", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("Integration testing was performed to verify that the modules work properly when connected together. Examples include attendance updates affecting payroll preview values, loan records being reflected in repayment lists, leave actions triggering notifications, and employee data being reused across payroll, performance, and membership screens."),
        para("SYSTEM MAINTENANCE", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("Maintenance is important for any live information system because business rules, users, and organizational policies change over time. The modular controller-model-view structure used in this project supports future enhancement, error correction, and adaptation to new operational requirements. Backups of the database and application files are also essential for safe long-term use."),
        pageBreak(),
        para("23", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("4.2 IMPLEMENTATION", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("The system is implemented in a local XAMPP environment using Apache, PHP, and MySQL. User authentication, role checking, and session handling are configured so that only authorized users can access the corresponding modules. After implementation, the portal supports day-to-day employee administration, payroll preparation, loan processing, repayment follow-up, and communication through one consistent interface."),
        para("The implemented solution fulfills the key project objectives by reducing manual work, improving data security, and ensuring that operational information is available quickly to the correct users. The application is simple enough for demonstration and academic evaluation while still representing a realistic organizational workflow."),
    ],
};

const conclusionSection = {
    properties: sectionProps(24),
    footers: { default: footer() },
    children: [
        para("5. CONCLUSION AND FUTURE ENHANCEMENT", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("5.1 CONCLUSION", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("The Dynamic Interaction Web Portal for Human Resource and Employee Credit Repay Process has been developed as an integrated application for employee administration and finance-related recovery processes. The system centralizes attendance, leave, payroll, loans, repayments, messages, notifications, and evaluation data so that organizational work can be carried out with better accuracy and visibility."),
        para("The use of PHP, MySQL, Bootstrap, and modular application design has made the software practical, secure, and extendable. The project demonstrates how routine office work can be transformed from manual records into structured digital operations that are easier to manage and report."),
        para("5.2 FUTURE ENHANCEMENT", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("The current portal provides the essential features required for employee and repayment management. In the future, the system can be enhanced with mobile access, stronger analytics dashboards, reminder automation for overdue repayment, richer approval workflows, and downloadable PDF reports for more modules."),
        para("Future development may also include SMS or WhatsApp alerts, biometric attendance integration, document attachment support for loans, online payment gateway integration, advanced audit trails, and multi-branch administration support."),
        pageBreak(),
        para("25", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("With these enhancements, the portal can evolve from an academic project into a more comprehensive enterprise tool for finance-oriented organizations. The present implementation establishes a strong foundation for that future growth by organizing the key operational flows in a secure and understandable manner."),
    ],
};

const bibliographySection = {
    properties: sectionProps(26),
    footers: { default: footer() },
    children: [
        para("6. BIBLIOGRAPHY", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("REFERENCE BOOKS", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        ...[
            "* Ian Sommerville, Software Engineering.",
            "* Roger S. Pressman, Software Engineering: A Practitioner's Approach.",
            "* Luke Welling and Laura Thomson, PHP and MySQL Web Development.",
            "* Jeffrey A. Hoffer, Joey F. George and Joseph S. Valacich, Modern Systems Analysis and Design.",
        ].map((line) => para(line, { alignment: AlignmentType.LEFT, firstLine: 0, after: 60 })),
        para("WEBSITES", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        ...[
            "* https://www.php.net",
            "* https://dev.mysql.com/doc",
            "* https://developer.mozilla.org",
            "* https://getbootstrap.com/docs/5.3",
            "* https://www.w3schools.com",
        ].map((line) => para(line, { alignment: AlignmentType.LEFT, firstLine: 0, after: 60 })),
    ],
};

const appendixSection = {
    properties: sectionProps(27),
    footers: { default: footer() },
    children: [
        para("APPENDIX - SAMPLE SCREENS", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        ...(fs.existsSync(images.landing) ? imageBlock(images.landing, "Landing Page") : [para("Landing page screenshot was not available.", { firstLine: 0 })]),
        pageBreak(),
        ...(fs.existsSync(images.login) ? imageBlock(images.login, "Secure Login Page") : [para("Login page screenshot was not available.", { firstLine: 0 })]),
    ],
};

const doc = new Document({
    sections: [
        tocSection,
        abstractSection,
        chapterTitleSection("INTRODUCTION"),
        introSection,
        chapterTitleSection("SYSTEM ANALYSIS"),
        analysisSection,
        chapterTitleSection("SYSTEM DESIGN"),
        designSection,
        chapterTitleSection("SYSTEM TESTING AND IMPLEMENTATION"),
        testingSection,
        chapterTitleSection("CONCLUSION"),
        conclusionSection,
        chapterTitleSection("BIBLIOGRAPHY"),
        bibliographySection,
        appendixSection,
    ],
});

Packer.toBuffer(doc).then((buffer) => {
    fs.writeFileSync(outputPath, buffer);
    console.log(`Saved ${outputPath}`);
}).catch((error) => {
    console.error(error);
    process.exitCode = 1;
});
