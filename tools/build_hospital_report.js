const fs = require("fs");
const path = require("path");
const {
    AlignmentType,
    BorderStyle,
    Document,
    Footer,
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

const projectRoot = "C:/Users/Sam/Desktop/hospital_management_system-master";
const outputPath = path.join(
    projectRoot,
    "Cloud Based Health Record Process and Appointment Creation Portal.docx",
);

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
    dfd: path.join(projectRoot, "dfd-level-0.png"),
    er: path.join(projectRoot, "er-diagram.png"),
    landing: path.join(projectRoot, "landing-screen.png"),
    login: path.join(projectRoot, "login-screen.png"),
};

const tableDesigns = [
    {
        number: "1",
        name: "Users",
        primaryKey: "id",
        foreignKey: "doctor_ref_id",
        fields: [
            ["id", "int", "255", "Unique user identification number"],
            ["username", "varchar", "255", "Username used to sign in to the system"],
            ["email", "varchar", "255", "Email address of the user"],
            ["password", "varchar", "20", "Password used for authentication"],
            ["email_status", "varchar", "20", "Verification or email activation status"],
            ["role", "varchar", "20", "Role such as patient, admin, or doctor"],
            ["doctor_ref_id", "int", "10", "Optional reference to the doctor master record"],
            ["is_active", "tinyint", "1", "Shows whether the account is active"],
        ],
    },
    {
        number: "2",
        name: "Doctor",
        primaryKey: "id",
        foreignKey: "",
        fields: [
            ["id", "int", "10", "Unique doctor identification number"],
            ["first_name", "varchar", "255", "Doctor first name"],
            ["last_name", "varchar", "255", "Doctor last name"],
            ["email", "varchar", "255", "Doctor email address"],
            ["dob", "varchar", "20", "Doctor date of birth"],
            ["gender", "varchar", "10", "Doctor gender"],
            ["phone", "varchar", "20", "Doctor contact number"],
            ["department", "varchar", "50", "Assigned medical department"],
            ["biography", "varchar", "255", "Short doctor profile"],
            ["doctor_salary", "decimal", "10,2", "Salary amount of the doctor"],
        ],
    },
    {
        number: "3",
        name: "Patients",
        primaryKey: "id",
        foreignKey: "",
        fields: [
            ["id", "int", "11", "Unique patient identification number"],
            ["patient_id", "varchar", "50", "Generated patient code"],
            ["first_name", "varchar", "120", "Patient first name"],
            ["last_name", "varchar", "120", "Patient last name"],
            ["gender", "varchar", "20", "Patient gender"],
            ["blood_group", "varchar", "10", "Patient blood group"],
            ["phone", "varchar", "30", "Patient phone number"],
            ["email", "varchar", "150", "Patient email address"],
            ["emergency_contact", "varchar", "120", "Emergency contact details"],
        ],
    },
    {
        number: "4",
        name: "Appointment",
        primaryKey: "id",
        foreignKey: "patient_ref_id, doctor_ref_id",
        fields: [
            ["id", "int", "10", "Unique appointment identification number"],
            ["patient_ref_id", "int", "11", "Reference to patient record"],
            ["patient_name", "varchar", "255", "Patient name for display"],
            ["department", "varchar", "255", "Selected department"],
            ["doctor_name", "varchar", "255", "Selected doctor name"],
            ["doctor_ref_id", "int", "11", "Reference to doctor record"],
            ["date", "varchar", "255", "Appointment date"],
            ["time", "varchar", "255", "Appointment time"],
            ["appointment_status", "varchar", "20", "Current appointment status"],
        ],
    },
    {
        number: "5",
        name: "Patient History",
        primaryKey: "id",
        foreignKey: "patient_ref_id",
        fields: [
            ["id", "int", "11", "Unique history identification number"],
            ["patient_ref_id", "int", "11", "Reference to patient master record"],
            ["doctor_name", "varchar", "150", "Doctor associated with the visit"],
            ["diagnosis", "varchar", "255", "Diagnosis details"],
            ["notes", "text", "-", "Clinical notes and remarks"],
            ["prescription", "text", "-", "Medicines or prescription details"],
            ["visit_date", "varchar", "30", "Visit date"],
            ["created_at", "timestamp", "-", "Record creation time"],
        ],
    },
    {
        number: "6",
        name: "Treatment Process",
        primaryKey: "id",
        foreignKey: "patient_ref_id, history_ref_id",
        fields: [
            ["id", "int", "11", "Unique treatment identification number"],
            ["patient_ref_id", "int", "11", "Reference to patient record"],
            ["history_ref_id", "int", "11", "Reference to patient history entry"],
            ["disease", "varchar", "255", "Disease or condition name"],
            ["procedure_name", "varchar", "255", "Treatment or procedure name"],
            ["medications", "text", "-", "Medicine details used in treatment"],
            ["follow_up_date", "varchar", "30", "Follow-up date"],
            ["treatment_notes", "text", "-", "Detailed treatment notes"],
            ["treatment_date", "varchar", "30", "Treatment date"],
        ],
    },
];

const run = (text, options = {}) =>
    new TextRun({
        text,
        font: options.font || "Times New Roman",
        size: options.size || 24,
        bold: Boolean(options.bold),
        italics: Boolean(options.italics),
    });

const para = (text = "", options = {}) =>
    new Paragraph({
        alignment: options.alignment || AlignmentType.JUSTIFIED,
        spacing: {
            before: options.before || 0,
            after: options.after !== undefined ? options.after : 120,
            line: options.line || 240,
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
                children: [new TextRun({ children: [PageNumber.CURRENT], size: 22, font: "Times New Roman" })],
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
        return [
            para(`[Space left for ${caption}]`, {
                alignment: AlignmentType.CENTER,
                firstLine: 0,
                italics: true,
                after: 80,
            }),
            ...blankLines(18),
        ];
    }

    return [
        para(caption, {
            alignment: AlignmentType.CENTER,
            size: 22,
            firstLine: 0,
            after: 80,
            italics: true,
        }),
        ...blankLines(18),
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
        para("CONTENT                                                                                                         Page No.", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 40 }),
        para("ABSTRACT", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("1. INTRODUCTION                                                                                                      1", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("1.1 OVERVIEW OF THE PROJECT", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("1.2 MODULE DESCRIPTION", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("1.3 SYSTEM SPECIFICATION", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("1.3.1 HARDWARE SPECIFICATION", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("1.3.2 SOFTWARE SPECIFICATION", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("1.4 ABOUT THE SOFTWARE", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("1.4.1 FRONT END", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("1.4.2 BACK END", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("2. SYSTEM ANALYSIS                                                                                                  9", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("2.1 EXISTING SYSTEM", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("2.1.1 DISADVANTAGES OF THE EXISTING SYSTEM", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("2.2 PROPOSED SYSTEM", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
        para("2.2.1 ADVANTAGES OF THE PROPOSED SYSTEM", { size: 20, alignment: AlignmentType.LEFT, firstLine: 0, after: 0 }),
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
        para('The project entitled "Cloud Based Health Record Process and Appointment Creation Portal" is designed to manage hospital records through a centralized digital environment. The system supports doctor registration, patient registration, appointment scheduling, patient history maintenance, treatment process recording, treatment space allocation, and status tracking in one integrated platform.'),
        para("In the implemented project, the application is developed as a Node.js and Express based web system with EJS views, HTML, CSS, JavaScript, Bootstrap, and a MySQL database. The portal provides separate role-based access for administrator, doctor, and patient users so that day-to-day activities can be handled through secure login and structured forms."),
        para("The system helps hospital staff access previous visit histories, doctor details, treatment updates, room or bed allocation details, and current patient status without depending on paper files. It also includes supporting modules for departments, employee records, leave management, payroll, medicine stock, complaints, and dashboard-based reporting."),
        para("By replacing manual record handling with a cloud-oriented web application, the project reduces paperwork, avoids duplication, improves record accuracy, and supports faster decision-making. The application offers a user-friendly working environment and keeps the core hospital data in a consistent and retrievable form for future use."),
    ],
};

const introSection = {
    properties: sectionProps(1),
    footers: { default: footer() },
    children: [
        para("1. INTRODUCTION", { bold: true }),
        para("1.1 OVERVIEW OF THE PROJECT", { size: 24, bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("Hospital management requires continuous coordination among doctors, patients, administrators, reception staff, and supporting departments. When records are maintained through registers, loose files, and disconnected manual procedures, searching for a patient file, reviewing previous treatment, checking doctor availability, or allocating a room becomes slow and error-prone."),
        para("The Cloud Based Health Record Process and Appointment Creation Portal addresses this problem by organizing hospital activities through a centralized web application. In this project, the portal is presented under the MediBook interface and combines patient, doctor, appointment, treatment, space allocation, payroll, store, and dashboard functions inside one database-driven system."),
        para("The implemented application is built using Node.js, Express.js, EJS templates, HTML, CSS, JavaScript, Bootstrap, and MySQL. It provides role-based access for administrators, doctors, and patients, making the system suitable for managing both hospital operations and patient-facing services through a browser-based environment."),
        para("By automating routine operations and maintaining the core records in one database, the system improves speed, visibility, record accuracy, and coordination across the hospital environment."),
        para("MODULES:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 200 }),
        ...[
            "1. Authentication and User Access Module",
            "2. Doctor Registration and Department Management Module",
            "3. Patient Registration Module",
            "4. Patient History Module",
            "5. Appointment Creation Module",
            "6. Treatment Space Allocation Module",
            "7. Treatment Process and Status Tracker Module",
            "8. Employee, Leave, and Payroll Module",
            "9. Medicine Store and Complaint Handling Module",
            "10. Dashboard, Profile, and Reporting Module",
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
        para("1. Authentication and User Access Module:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, left: 360, hanging: 360, after: 80 }),
        para("This module manages login, signup, password reset, verification, session handling, and role-based access for administrator, doctor, and patient users.", { firstLine: 360, after: 120 }),
        para("2. Doctor Registration and Department Management Module:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, left: 360, hanging: 360, after: 80 }),
        para("This module stores doctor profile details such as name, contact information, department, biography, and salary information, while also maintaining department master records used across the system.", { firstLine: 360, after: 120 }),
        para("3. Patient Registration Module:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, left: 360, hanging: 360, after: 80 }),
        para("This module creates patient master records with patient ID, demographic details, blood group, contact information, and emergency contact details.", { firstLine: 360, after: 120 }),
        para("4. Patient History Module:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, left: 360, hanging: 360, after: 80 }),
        para("This module stores visit-wise diagnosis, prescription, notes, doctor reference, and visit date so that previous clinical history can be reviewed whenever needed.", { firstLine: 360, after: 120 }),
        para("5. Appointment Creation Module:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, left: 360, hanging: 360, after: 80 }),
        para("This module creates appointment entries between patients and doctors, records date and time values, and tracks appointment status for scheduling purposes.", { firstLine: 360, after: 120 }),
        para("6. Treatment Space Allocation Module:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, left: 360, hanging: 360, after: 80 }),
        para("This module manages ward, room, and bed details, allocates spaces to patients, records discharge dates, and supports space availability monitoring.", { firstLine: 360, after: 120 }),
        para("7. Treatment Process and Status Tracker Module:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, left: 360, hanging: 360, after: 80 }),
        para("This module stores disease details, treatment procedures, medicine plans, follow-up dates, and status updates such as admitted, in-treatment, recovering, or discharged.", { firstLine: 360, after: 120 }),
        para("8. Employee, Leave, and Payroll Module:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, left: 360, hanging: 360, after: 80 }),
        para("This module manages employee records, leave requests, approvals, salary details, and payslip generation for hospital staff.", { firstLine: 360, after: 120 }),
        para("9. Medicine Store and Complaint Handling Module:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, left: 360, hanging: 360, after: 80 }),
        para("This module stores medicine inventory details, expiry information, quantity, and price values, and also records complaints or public messages.", { firstLine: 360, after: 120 }),
        para("10. Dashboard, Profile, and Reporting Module:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, left: 360, hanging: 360, after: 80 }),
        para("This module provides home dashboard, admin dashboard, doctor dashboard, patient dashboard, profile views, and export or report outputs for better monitoring.", { firstLine: 360, after: 200 }),
        pageBreak(),
        para("3", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("1.3 SYSTEM SPECIFICATION", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 200 }),
        para("1.3.1 HARDWARE SPECIFICATION", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        ...[
            "Processor              :   Intel Core i3 / i5 or above",
            "RAM                    :   4 GB or above",
            "Hard Disk              :   250 GB or above",
            "Monitor                :   Standard color monitor",
            "Keyboard               :   Standard keyboard",
            "Mouse                  :   Optical mouse",
        ].map((line) => para(line, { alignment: AlignmentType.LEFT, firstLine: 0, after: 80 })),
        para("1.3.2 SOFTWARE SPECIFICATION", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        ...[
            "Front End              :   HTML, CSS, JavaScript, Bootstrap, EJS",
            "Back End               :   Node.js with Express.js",
            "Database               :   MySQL",
            "Web Server             :   Node.js local server",
            "IDE / Editor           :   Visual Studio Code",
            "Web Browser            :   Google Chrome, Microsoft Edge, Mozilla Firefox",
        ].map((line) => para(line, { alignment: AlignmentType.LEFT, firstLine: 0, after: 80 })),
        pageBreak(),
        para("4", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("1.4 ABOUT THE SOFTWARE", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 200 }),
        para("1.4.1 FRONT END", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("HTML, CSS, JavaScript, Bootstrap, and EJS", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("The front end of the project is built using HTML, CSS, JavaScript, Bootstrap, and EJS templates. These technologies provide responsive forms, navigation menus, dashboards, tables, and page layouts that allow users to enter and view hospital data through a browser in a structured manner."),
        para("The system uses clear form controls, tables, cards, public landing content, and role-based dashboard screens so that users can work with the application comfortably. EJS helps render dynamic values received from the server while keeping the page structure readable."),
        para("Features of the Front End", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 120 }),
        ...[
            "* Provides clear page structure and responsive layouts",
            "* Supports reusable templates and dynamic page rendering",
            "* Works across modern browsers",
            "* Integrates easily with validation and dashboard views",
        ].map((line) => para(line, { alignment: AlignmentType.LEFT, firstLine: 0, after: 60 })),
        para("The front-end stack supports public pages, login pages, profile pages, appointment screens, patient history views, payroll screens, and store-related pages in a consistent visual style. This improves usability during both academic demonstration and real record handling."),
        pageBreak(),
        para("5", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("1.4.2 BACK END", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("Node.js and Express.js", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("The back end of the project is developed using Node.js and Express.js with MySQL as the database. Controllers handle routing, validation, CRUD operations, and role-based workflows, while the database stores doctor, patient, appointment, treatment, employee, and stock records for consistent retrieval and reporting."),
        para("The application is organized through controllers such as login, home, patients, appointment, doctor dashboard, patient dashboard, employee, receipt, store, and related support modules. This structure improves modularity and makes the system easier to maintain."),
        para("Features of the Back End", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 120 }),
        ...[
            "* Supports server-side processing and session handling",
            "* Integrates easily with MySQL databases",
            "* Organizes hospital workflows through route and controller logic",
            "* Supports dynamic rendering of views and reports",
        ].map((line) => para(line, { alignment: AlignmentType.LEFT, firstLine: 0, after: 60 })),
        para("MySQL", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("MySQL is used as the relational database for storing users, doctors, patients, appointments, history, treatment details, treatment spaces, employee records, leave details, medicine stock, and related operational data. Centralized database storage helps the hospital retrieve and update records quickly."),
        pageBreak(),
        para("6", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("SYSTEM HIGHLIGHTS", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("The hospital portal supports multi-role operation, secure login, doctor registration, patient history maintenance, appointment scheduling, treatment space allocation, status tracking, employee and payroll maintenance, medicine store management, and dashboard-based reporting."),
        para("Because the project is separated into views, controllers, and database tables, it can be extended in the future without changing the full structure. This makes the system suitable for both project demonstration and future enhancement."),
        para("Major Benefits of the Implemented Software", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 120 }),
        ...[
            "* Reduces manual paperwork in hospital record maintenance",
            "* Improves visibility of patient and treatment information",
            "* Supports role-based operation for different users",
            "* Provides a clean base for further hospital modules",
        ].map((line) => para(line, { alignment: AlignmentType.LEFT, firstLine: 0, after: 60 })),
    ],
};

const analysisSection = {
    properties: sectionProps(9),
    footers: { default: footer() },
    children: [
        para("2. SYSTEM ANALYSIS", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("2.1 EXISTING SYSTEM", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("In many hospitals and clinics, patient details, appointment notes, treatment records, and room allocation data are still maintained through paper files or loosely connected manual registers. Searching for previous history or checking real-time status requires physical verification and repeated communication among staff members."),
        para("Manual handling of hospital data delays patient service, increases the risk of duplicate entries, and makes it difficult to generate reports quickly. Coordination between doctor, administrator, patient, and support units becomes inefficient when information is spread across multiple files or informal records."),
        para("2.1.1 DISADVANTAGES OF THE EXISTING SYSTEM", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        ...[
            "* Manual maintenance of patient and appointment data is time-consuming.",
            "* Searching for previous treatment history is difficult and slow.",
            "* Paper-based room or bed allocation can lead to mistakes and duplication.",
            "* Generating status reports and summaries requires extra manual effort.",
            "* Data security and long-term record preservation are weak in manual systems.",
            "* Coordination among departments is delayed when information is not centralized.",
        ].map((line) => para(line, { alignment: AlignmentType.LEFT, firstLine: 0, after: 60 })),
        para("2.2 PROPOSED SYSTEM", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("The proposed system computerizes hospital workflows through a web-based portal. It stores doctor details, patient records, patient history, appointment schedules, treatment process updates, space allocation data, employee records, medicine stock, and dashboard summaries in a centralized MySQL database."),
        para("Because the system is role-based, the administrator can manage core hospital data, doctors can access relevant treatment information, and patients can review appointments and profile details through a secure interface. The proposed design improves retrieval speed, accuracy, visibility, and operational control."),
        para("2.2.1 ADVANTAGES OF THE PROPOSED SYSTEM", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        ...[
            "* Centralized storage of patient, doctor, and appointment records.",
            "* Faster access to treatment history and current status information.",
            "* Better room, ward, and bed allocation tracking.",
            "* Reduced paperwork and fewer manual errors.",
            "* Secure login and role-based access for different users.",
            "* Improved reporting, dashboard visibility, and workflow efficiency.",
        ].map((line) => para(line, { alignment: AlignmentType.LEFT, firstLine: 0, after: 60 })),
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
        para("At Level 1, the overall portal is decomposed into modules such as authentication, doctor management, patient management, appointment processing, patient history, treatment process, treatment space allocation, status tracking, payroll, store management, and dashboard reporting. Each process receives structured input from users and stores validated results in the centralized database."),
        para("Administrators manage the core hospital records, doctors update treatment-related details, and patients access appointment and profile information. Outputs are displayed as dashboards, lists, reports, payslip views, patient records, and status summaries."),
        pageBreak(),
        para("13", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("LEVEL 2:", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("At Level 2, the detailed process flow covers validation, storage, status change, and related updates within each hospital module. Appointment entries connect patients and doctors, patient history supports treatment review, treatment space allocation updates room usage, and status tracking records the progress of care."),
        para("This decomposition helps separate responsibilities clearly and ensures that the system can be maintained or extended without affecting unrelated modules. The use of dedicated controllers, views, and SQL tables in the application supports this modular design approach."),
        pageBreak(),
        para("14", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("3.1.2 ENTITY RELATIONSHIP DIAGRAM", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        ...imageBlock(images.er, "Entity Relationship Diagram"),
        pageBreak(),
        para("15", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("3.2 DESIGN PROCESS", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("3.2.1 INPUT DESIGN", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("Input design is one of the most important phases in the development of a computerized information system because output quality depends on the accuracy of the entered data. The proposed portal uses clear forms, validation messages, dropdowns, date fields, and protected actions to reduce entry errors and guide users according to their role."),
        ...[
            "1. Login Page - Accepts username or email and password for secure access.",
            "2. Signup and Verification Pages - Collect new user details and support verification or password reset.",
            "3. Add Doctor Page - Captures doctor profile details, department, contact information, and salary.",
            "4. Add Department Page - Captures department name and department description.",
            "5. Add Patient Page - Captures patient ID, personal details, blood group, contact details, and emergency contact.",
            "6. Add Appointment Page - Captures patient, doctor, department, date, time, email, and phone values.",
            "7. Add Patient History Page - Captures diagnosis, notes, prescription, doctor name, and visit date.",
            "8. Add Treatment Space and Allocation Pages - Capture ward, room, bed, allocation date, discharge date, and remarks.",
            "9. Add Treatment Process Page - Captures disease, procedure name, medications, treatment date, follow-up date, and treatment notes.",
            "10. Add Patient Status Page - Captures patient status label, status time, and remarks.",
            "11. Employee, Leave, Payroll, and Store Pages - Capture staff details, leave data, salary data, and medicine stock values.",
        ].map((line) => para(line, { alignment: AlignmentType.LEFT, firstLine: 0, after: 70 })),
        para("These pages directly support the hospital workflow and ensure that incomplete or invalid values are reduced before storage."),
        pageBreak(),
        para("16", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("3.2.2 DATABASE DESIGN", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("Database design is used to organize hospital information in a structured and reusable form. The project uses MySQL as the backend database. The data is divided into separate tables for users, departments, doctors, patients, appointments, patient history, status tracking, treatment spaces, space allocations, treatment process, employees, leaves, store items, and supporting records."),
        para("Primary keys uniquely identify records, while foreign keys preserve relationships across modules. For example, patient history and treatment process tables are linked to patient records, and appointment records are linked to both patient and doctor data. This normalized structure reduces redundancy and supports faster retrieval during reporting and monitoring."),
        para("The database design also includes status fields and timestamps so that the system can track active records, workflow changes, and operational history. This makes the portal suitable for both routine daily use and future expansion."),
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
        para("Output design refers to the way processed information is presented to users. In this portal, outputs are role-based and easy to interpret. Administrators see summary cards, process lists, status views, report links, and operational tables, while doctors and patients can view the pages that relate directly to their work or treatment flow."),
        ...[
            "* Landing Page: Presents the project overview and core hospital feature blocks.",
            "* Login Page: Provides secure entry to the role-based portal.",
            "* Home, Admin, Doctor, and Patient Dashboards: Show operational summary values for each role.",
            "* Patients and Appointments Views: Display patient master records and appointment details.",
            "* Patient History, Treatment, Space Allocation, and Status Views: Present the medical workflow and current care position.",
            "* Employee, Leave, Payroll, and Payslip Views: Display staff-related and salary-related details.",
            "* Store and Report Views: Display medicine stock information and export-related output.",
        ].map((line) => para(line, { alignment: AlignmentType.LEFT, firstLine: 0, after: 60 })),
    ],
};

const testingSection = {
    properties: sectionProps(21),
    footers: { default: footer() },
    children: [
        para("4. SYSTEM TESTING AND IMPLEMENTATION", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("4.1 SYSTEM TESTING", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("Testing is a necessary phase in software development because it verifies that the implemented modules satisfy user requirements and behave correctly under practical conditions. In this project, testing focuses on functional correctness, role-based access, validation accuracy, database integrity, and consistency of the hospital workflow."),
        para("UNIT TESTING", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("Each major module such as login, doctors, patients, appointments, patient history, treatment process, space allocation, status tracking, employees, payroll, and store management was tested independently so that input, processing, and output could be verified in isolation. Separate testing of modules helped detect validation and data-handling issues before integration."),
        para("VALIDATION TESTING", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("Validation testing ensures that only acceptable values are entered into the system. Email fields, dates, required identifiers, appointment values, salary values, and status choices are validated before records are saved. Invalid login, duplicate email, and incomplete form submission cases are handled carefully to prevent incorrect data entry."),
        pageBreak(),
        para("22", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("INTEGRATION TESTING", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("Integration testing was performed to verify that the modules work properly when connected together. Examples include patient data being reused in appointment and history screens, doctor records being used in appointment creation, treatment spaces being reflected in allocation pages, and dashboard counts being generated from multiple related tables."),
        para("SYSTEM MAINTENANCE", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("Maintenance is important for any live information system because business rules, users, and organizational policies change over time. The modular controller-model-view structure used in this project supports future enhancement, error correction, and adaptation to new operational requirements. Backups of the database and application files are also essential for safe long-term use."),
        pageBreak(),
        para("23", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("4.2 IMPLEMENTATION", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("The system is implemented as a web application using Node.js, Express.js, EJS, and MySQL. User authentication, role checking, and session handling are configured so that only authorized users can access the corresponding modules. After implementation, the portal supports day-to-day patient administration, appointment handling, treatment monitoring, room allocation, payroll activity, and medicine stock maintenance through one consistent interface."),
        para("The implemented solution fulfills the key project objectives by reducing manual work, improving data security, and ensuring that operational information is available quickly to the correct users. The application is simple enough for demonstration and academic evaluation while still representing a realistic hospital workflow."),
    ],
};

const conclusionSection = {
    properties: sectionProps(24),
    footers: { default: footer() },
    children: [
        para("5. CONCLUSION AND FUTURE ENHANCEMENT", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("5.1 CONCLUSION", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("The Cloud Based Health Record Process and Appointment Creation Portal has been developed as an integrated application for hospital administration and patient care workflow management. The system centralizes doctor records, patient records, appointments, patient history, treatment flow, treatment spaces, status tracking, and supporting administrative modules so that work can be carried out with better accuracy and visibility."),
        para("The use of Node.js, Express.js, EJS, MySQL, Bootstrap, and modular application design has made the software practical, secure, and extendable. The project demonstrates how routine hospital work can be transformed from manual records into structured digital operations that are easier to manage and report."),
        para("5.2 FUTURE ENHANCEMENT", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        para("The current portal provides the essential features required for hospital record management. In the future, the system can be enhanced with online billing, lab report integration, PDF prescription generation, mobile access, stronger analytics dashboards, and downloadable reports for more modules."),
        para("Future development may also include SMS or email alerts, pharmacy billing integration, laboratory workflow integration, advanced audit trails, and multi-branch hospital administration support."),
        pageBreak(),
        para("25", { font: "Calibri", size: 22, bold: true, alignment: AlignmentType.CENTER, firstLine: 0, after: 240 }),
        para("With these enhancements, the portal can evolve from an academic project into a more comprehensive digital platform for healthcare institutions. The present implementation establishes a strong foundation for that future growth by organizing the key operational flows in a secure and understandable manner."),
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
            "* Julie C. Meloni, Sams Teach Yourself Node.js in 24 Hours.",
            "* Jeffrey A. Hoffer, Joey F. George and Joseph S. Valacich, Modern Systems Analysis and Design.",
        ].map((line) => para(line, { alignment: AlignmentType.LEFT, firstLine: 0, after: 60 })),
        para("WEBSITES", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        ...[
            "* https://nodejs.org",
            "* https://expressjs.com",
            "* https://ejs.co",
            "* https://dev.mysql.com/doc",
            "* https://developer.mozilla.org",
            "* https://www.w3schools.com",
        ].map((line) => para(line, { alignment: AlignmentType.LEFT, firstLine: 0, after: 60 })),
    ],
};

const appendixSection = {
    properties: sectionProps(27),
    footers: { default: footer() },
    children: [
        para("APPENDIX - SAMPLE SCREENS", { bold: true, alignment: AlignmentType.LEFT, firstLine: 0, after: 160 }),
        ...imageBlock(images.landing, "Landing Page / Login Page"),
        pageBreak(),
        ...imageBlock(images.login, "Admin Dashboard / Patient Dashboard"),
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
