<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if user is logged in and is a mentor
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'mentor' || !isset($_SESSION['mentor_id'])) {
    header("Location: login.php");
    exit();
}

// Debug session information
echo "<!-- Debug Information:
Session Contents:
";
var_export($_SESSION);
echo "
-->";

require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mentor Dashboard</title>
    <style>
        /* Reset default styles */
        button {
            all: unset;
            box-sizing: border-box;
        }

        /* Button styles */
        button.btn-base {
            position: relative !important;
            border: none !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 1.5px !important;
            transition: all 0.3s ease !important;
            overflow: hidden !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
            cursor: pointer !important;
            font-size: 0.9rem !important;
            padding: 10px 20px !important;
            color: white !important;
            background: linear-gradient(135deg, #3498db, #2980b9) !important;
            display: inline-block !important;
        }

        button.btn-base:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4) !important;
        }

        button.modify-btn {
            background: linear-gradient(135deg, #2ecc71, #27ae60) !important;
            margin-right: 5px !important;
        }

        button.delete-btn {
            background: linear-gradient(135deg, #e74c3c, #c0392b) !important;
        }

        button.ripple {
            position: relative;
            overflow: hidden;
        }

        button.ripple::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            background-image: radial-gradient(circle, rgba(255, 255, 255, 0.3) 10%, transparent 10.01%);
            background-repeat: no-repeat;
            background-position: 50%;
            transform: scale(10, 10);
            opacity: 0;
            transition: transform 0.5s, opacity 1s;
        }

        button.ripple:active::after {
            transform: scale(0, 0);
            opacity: 0.3;
            transition: 0s;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal-content {
            background-color: #1e1e2f;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            position: relative;
            max-height: 80vh;
            overflow-y: auto;
        }

        /* Close button for modals */
        .modal-close {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            color: #fff;
            cursor: pointer;
            background: none;
            border: none;
            padding: 5px;
        }

        .modal-close:hover {
            color: #e74c3c;
        }

        /* Form styles */
        .modal-content input {
            width: 100% !important;
            padding: 12px !important;
            margin-bottom: 1rem !important;
            border: 1px solid #2c2c44 !important;
            border-radius: 8px !important;
            background-color: #151521 !important;
            color: white !important;
            font-size: 1rem !important;
            box-sizing: border-box !important;
        }

        .modal-content input:focus {
            outline: none !important;
            border-color: #3498db !important;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2) !important;
        }

        /* Table styles */
        #studentsTable {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 2rem 0;
            background-color: #151521;
            border-radius: 10px;
            overflow: hidden;
        }

        #studentsTable th {
            background-color: #3498db;
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }

        #studentsTable td {
            padding: 1rem;
            border-bottom: 1px solid #2c2c44;
            color: #b3b3b3;
        }

        #studentsTable tr:hover td {
            background-color: rgba(52, 152, 219, 0.1);
            color: white;
        }

        /* Header area */
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background-color: #151521;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .header-container h1 {
            margin: 0;
            font-size: 24px;
            color: white;
            font-weight: 600;
        }

        .logout-form {
            margin: 0;
        }

        button.logout-btn {
            background: linear-gradient(135deg, #e74c3c, #c0392b) !important;
            color: white !important;
            padding: 10px 24px !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 1.5px !important;
            transition: all 0.3s ease !important;
            cursor: pointer !important;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3) !important;
            font-size: 14px !important;
        }

        button.logout-btn:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4) !important;
        }

        /* Content area */
        .content-container {
            padding: 0 40px;
        }

        /* Add Student button with new color and fixed positioning */
        button.add-student-btn {
            background: linear-gradient(135deg, #9b59b6, #8e44ad) !important;
            color: white !important;
            padding: 12px 28px !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 1.5px !important;
            transition: all 0.3s ease !important;
            cursor: pointer !important;
            box-shadow: 0 4px 15px rgba(155, 89, 182, 0.3) !important;
            margin: 1rem 0 !important;
            display: inline-block !important;
            position: relative !important;
            z-index: 1 !important;
        }

        button.add-student-btn:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(155, 89, 182, 0.4) !important;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #fff;
            font-weight: 500;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #2c2c44;
            border-radius: 4px;
            background-color: #1e1e2f;
            color: #fff;
            font-size: 1rem;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        /* Add these styles to your existing CSS */
        .grade-summary {
            background: rgba(52, 152, 219, 0.1);
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }

        .grade-summary h4 {
            margin: 0 0 0.5rem 0;
            color: #3498db;
        }

        .grade-summary p {
            margin: 0.5rem 0;
            color: #fff;
        }

        .grade-summary span {
            font-weight: 600;
            color: #3498db;
        }

        /* Add input validation styles */
        input:invalid {
            border-color: #e74c3c !important;
        }

        input:valid {
            border-color: #2ecc71 !important;
        }
    </style>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="header-container">
    <h1>Welcome to Mentor Dashboard</h1>
        <form action="login.php" method="post" class="logout-form">
            <button type="submit" class="logout-btn ripple">Logout</button>
    </form>
    </div>

    <div class="content-container">
    <h2>Students</h2>
        <button onclick="showAddStudentModal()" class="add-student-btn ripple">Add Student</button>
    <table id="studentsTable">
        <thead>
            <tr>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Gender</th>
                <th>Email</th>
                <th>Course ID</th>
                <th>Course Name</th>
                <th>Birthdate</th>
                <th>Mobile</th>
                <th>Nationality</th>
                <th>State</th>
                <th>City</th>
                    <th>Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
    </div>

    <div id="addStudentModal" style="display:none;" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('addStudentModal')">&times;</button>
        <h3>Add New Student</h3>
        <form id="addStudentForm">
                <div class="form-group">
                    <input type="text" name="fname" placeholder="First Name" required>
                    <input type="text" name="lname" placeholder="Last Name" required>
                    <select name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                    <input type="email" name="email" placeholder="Email" required>
                    <select name="course_id" required>
                        <?php
                        $stmt = $conn->prepare("SELECT course_id, course_name FROM course_details");
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($course = $result->fetch_assoc()) {
                            echo "<option value='" . $course['course_id'] . "' data-name='" . htmlspecialchars($course['course_name']) . "'>" . 
                                 htmlspecialchars($course['course_name']) . "</option>";
                        }
                        $stmt->close();
                        ?>
                    </select>
                    <input type="hidden" name="course_name" id="selected_course_name">
                    <input type="date" name="birthdate" required>
                    <input type="tel" name="mobile" placeholder="Mobile Number" required pattern="[0-9]{10}">
                    <input type="text" name="nationality" placeholder="Nationality" required>
                    <input type="text" name="state" placeholder="State" required>
                    <input type="text" name="city" placeholder="City" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-base ripple">Add Student</button>
                    <button type="button" onclick="closeModal('addStudentModal')" class="btn-base ripple">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modifyStudentModal" style="display:none;" class="modal">
        <div class="modal-content">
            <h3>Modify Student</h3>
            <form id="modifyStudentForm">
                <input type="hidden" name="stud_id">
                <input type="text" name="fname" placeholder="First Name" required>
                <input type="text" name="lname" placeholder="Last Name" required>
                <input type="text" name="gender" placeholder="Gender" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="number" name="course_id" placeholder="Course ID" required>
                <input type="text" name="course_name" placeholder="Course Name" required>
                <input type="date" name="birthdate" required>
                <input type="text" name="mobile" placeholder="Mobile" required>
                <input type="text" name="nationality" placeholder="Nationality" required>
                <input type="text" name="state" placeholder="State" required>
                <input type="text" name="city" placeholder="City" required>
                <button type="submit" class="btn-base ripple">Update Student</button>
                <button type="button" onclick="closeModifyStudentModal()" class="btn-base ripple">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Add Attendance Modal -->
    <div id="addAttendanceModal" style="display:none;" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('addAttendanceModal')">&times;</button>
            <h3>Update Attendance</h3>
            <form id="addAttendanceForm">
                <input type="hidden" name="stud_id" id="attendance_stud_id">
                <div class="form-group">
                    <label for="subject_id">Select Subject:</label>
                    <select name="subject_id" id="subject_id" required>
                        <!-- Subject options will be loaded dynamically -->
                    </select>
                </div>
                <div class="form-group">
                    <label for="total_classes">Total Classes:</label>
                    <input type="number" name="total_classes" id="total_classes" required min="0">
                </div>
                <div class="form-group">
                    <label for="attended_classes">Attended Classes:</label>
                    <input type="number" name="attended_classes" id="attended_classes" required min="0">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-base ripple">Update Attendance</button>
                    <button type="button" onclick="closeModal('addAttendanceModal')" class="btn-base ripple">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Grades Modal -->
    <div id="addGradesModal" style="display:none;" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('addGradesModal')">&times;</button>
            <h3>Update Grades</h3>
            <form id="addGradesForm">
                <input type="hidden" name="stud_id" id="grades_stud_id">
                <div class="form-group">
                    <label for="subject_id">Select Subject:</label>
                    <select name="subject_id" id="grades_subject_id" required>
                        <?php
                        $stmt = $conn->prepare("SELECT subject_id, subject_name FROM subjects WHERE course_id = 101 ORDER BY subject_name");
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($subject = $result->fetch_assoc()) {
                            echo "<option value='" . $subject['subject_id'] . "'>" . htmlspecialchars($subject['subject_name']) . "</option>";
                        }
                        $stmt->close();
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="assignment_score">Assignment Score (30%):</label>
                    <input type="number" name="assignment_score" id="assignment_score" required min="0" max="100" step="0.01">
                </div>
                <div class="form-group">
                    <label for="midterm_score">Midterm Score (30%):</label>
                    <input type="number" name="midterm_score" id="midterm_score" required min="0" max="100" step="0.01">
                </div>
                <div class="form-group">
                    <label for="final_score">Final Score (40%):</label>
                    <input type="number" name="final_score" id="final_score" required min="0" max="100" step="0.01">
                </div>
                <div id="grade_summary" class="grade-summary" style="display: none;">
                    <h4>Grade Summary</h4>
                    <p>Total Score: <span id="total_score">0</span></p>
                    <p>Grade Letter: <span id="grade_letter">-</span></p>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-base ripple">Update Grades</button>
                    <button type="button" onclick="closeModal('addGradesModal')" class="btn-base ripple">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Achievement Modal -->
    <div id="addAchievementModal" style="display:none;" class="modal">
        <div class="modal-content">
            <h3>Add Achievement</h3>
            <form id="addAchievementForm">
                <input type="hidden" name="stud_id" id="achievement_stud_id">
                <input type="text" name="achievement_title" placeholder="Achievement Title" required>
                <input type="date" name="achievement_date" required>
                <textarea name="description" placeholder="Achievement Description" required></textarea>
                <input type="text" name="awarded_by" placeholder="Awarded By" required>
                <button type="submit" class="btn-base ripple">Add Achievement</button>
                <button type="button" onclick="closeModal('addAchievementModal')" class="btn-base ripple">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Add Mentor Note Modal -->
    <div id="addNoteModal" style="display:none;" class="modal">
        <div class="modal-content">
            <h3>Add Mentor Note</h3>
            <form id="addNoteForm">
                <input type="hidden" name="stud_id" id="note_stud_id">
                <select name="note_type" required>
                    <option value="Academic">Academic</option>
                    <option value="Behavioral">Behavioral</option>
                    <option value="Performance">Performance</option>
                    <option value="General">General</option>
                </select>
                <textarea name="note_content" placeholder="Note Content" required></textarea>
                <button type="submit" class="btn-base ripple">Add Note</button>
                <button type="button" onclick="closeModal('addNoteModal')" class="btn-base ripple">Cancel</button>
        </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', fetchStudents);

        function fetchStudents() {
            fetch('mentors/get_students.php')
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(result => {
                    console.log('Fetched data:', result);
                    const tbody = document.querySelector('#studentsTable tbody');
                    tbody.innerHTML = '';
                    
                    if (result.status === 'success' && result.data && result.data.length > 0) {
                        result.data.forEach(student => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${student.fname || ''}</td>
                                <td>${student.lname || ''}</td>
                                <td>${student.gender || ''}</td>
                                <td>${student.email || ''}</td>
                                <td>${student.course_id || ''}</td>
                                <td>${student.course_name || ''}</td>
                                <td>${student.birthdate || ''}</td>
                                <td>${student.mobile || ''}</td>
                                <td>${student.nationality || ''}</td>
                                <td>${student.state || ''}</td>
                                <td>${student.city || ''}</td>
                                <td>
                                    <button onclick="modifyStudent(${JSON.stringify(student).replace(/"/g, '&quot;')})" class="modify-btn btn-base ripple">Modify</button>
                                    <button onclick="deleteStudent(${student.stud_id})" class="delete-btn btn-base ripple">Delete</button>
                                    <button onclick="showAttendanceModal(${student.stud_id})" class="btn-base ripple">Attendance</button>
                                    <button onclick="showGradesModal(${student.stud_id})" class="btn-base ripple">Grades</button>
                                    <button onclick="showAchievementModal(${student.stud_id})" class="btn-base ripple">Achievement</button>
                                    <button onclick="showNoteModal(${student.stud_id})" class="btn-base ripple">Add Note</button>
                                </td>
                            `;
                            tbody.appendChild(row);
                        });
                    } else {
                        const row = document.createElement('tr');
                        row.innerHTML = '<td colspan="12">No students found</td>';
                        tbody.appendChild(row);
                    }
                })
                .catch(error => {
                    console.error('Error fetching students:', error);
                    const tbody = document.querySelector('#studentsTable tbody');
                    tbody.innerHTML = '<tr><td colspan="12">Error loading student data. Check console for details.</td></tr>';
                });
        }

        function showAddStudentModal() {
            document.getElementById('addStudentModal').style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto'; // Restore scrolling
            // Reset form if it's the add student modal
            if (modalId === 'addStudentModal') {
                document.getElementById('addStudentForm').reset();
            }
        }

        function deleteStudent(studId) {
            if (confirm('Are you sure you want to delete this student?')) {
                fetch('mentors/delete_student.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ stud_id: studId })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        alert('Student deleted successfully!');
                        fetchStudents(); // Refresh the table
                    } else {
                        alert(result.message || 'Error deleting student');
                    }
                })
                .catch(error => {
                    console.error('Delete error:', error);
                    alert('Error deleting student.');
                });
            }
        }

        function modifyStudent(student) {
            const form = document.getElementById('modifyStudentForm');
            // Fill the form with student data
            form.stud_id.value = student.stud_id;
            form.fname.value = student.fname;
            form.lname.value = student.lname;
            form.gender.value = student.gender;
            form.email.value = student.email;
            form.course_id.value = student.course_id;
            form.course_name.value = student.course_name;
            form.birthdate.value = student.birthdate;
            form.mobile.value = student.mobile;
            form.nationality.value = student.nationality;
            form.state.value = student.state;
            form.city.value = student.city;

            // Show the modal
            document.getElementById('modifyStudentModal').style.display = 'block';
        }

        function closeModifyStudentModal() {
            document.getElementById('modifyStudentModal').style.display = 'none';
        }

        document.getElementById('modifyStudentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });

            fetch('mentors/update_student.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    alert('Student updated successfully!');
                    closeModifyStudentModal();
                    fetchStudents();
                } else {
                    alert(result.message || 'Error updating student');
                }
            })
            .catch(error => {
                console.error('Update error:', error);
                alert('Error updating student.');
            });
        });

        document.getElementById('addStudentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = {};
            
            // Get the selected course name from the data attribute
            const courseSelect = this.querySelector('select[name="course_id"]');
            const selectedOption = courseSelect.options[courseSelect.selectedIndex];
            formData.set('course_name', selectedOption.getAttribute('data-name'));
            
            formData.forEach((value, key) => {
                data[key] = value;
            });

            console.log('Sending data:', data);

            fetch('mentors/add_students.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(result => {
                console.log('Server response:', result);
                    if (result.status === 'success') {
                        alert('Student added successfully!');
                    this.reset();
                    closeModal('addStudentModal');
                        fetchStudents();
                    } else {
                        alert(result.message || 'Error adding student');
                    }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting form. Check console for details.');
            });
        });

        // Add change event listener to course_id select
        document.querySelector('select[name="course_id"]').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('selected_course_name').value = selectedOption.getAttribute('data-name');
        });

        function showAttendanceModal(studId) {
            document.getElementById('attendance_stud_id').value = studId;
            
            // Fetch subjects for the student's course
            fetch('mentors/get_subjects.php?stud_id=' + studId)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const subjectSelect = document.getElementById('subject_id');
                        subjectSelect.innerHTML = ''; // Clear existing options
                        
                        data.subjects.forEach(subject => {
                            const option = document.createElement('option');
                            option.value = subject.subject_id;
                            option.textContent = subject.subject_name;
                            subjectSelect.appendChild(option);
                        });
                        
                        document.getElementById('addAttendanceModal').style.display = 'block';
                    } else {
                        alert('Error loading subjects: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading subjects');
                });
        }

        function showGradesModal(studId) {
            document.getElementById('grades_stud_id').value = studId;
            document.getElementById('grade_summary').style.display = 'none';
            document.getElementById('addGradesModal').style.display = 'block';
        }

        function showAchievementModal(studId) {
            document.getElementById('achievement_stud_id').value = studId;
            document.getElementById('addAchievementModal').style.display = 'block';
        }

        function showNoteModal(studId) {
            document.getElementById('note_stud_id').value = studId;
            document.getElementById('addNoteModal').style.display = 'block';
        }

        document.getElementById('addAttendanceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });

            // Validate attendance numbers
            if (parseInt(data.attended_classes) > parseInt(data.total_classes)) {
                alert('Attended classes cannot be more than total classes');
                return;
            }

            fetch('mentors/update_attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    alert('Attendance updated successfully!');
                    this.reset();
                    closeModal('addAttendanceModal');
                } else {
                    alert(result.message || 'Error updating attendance');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating attendance.');
            });
        });

        document.getElementById('addGradesForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });

            fetch('mentors/update_grades.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    alert('Grades updated successfully!');
                    document.getElementById('grade_summary').style.display = 'block';
                    document.getElementById('total_score').textContent = result.data.total_score;
                    document.getElementById('grade_letter').textContent = result.data.grade_letter;
                    setTimeout(() => {
                        this.reset();
                        closeModal('addGradesModal');
                    }, 2000);
                } else {
                    alert(result.message || 'Error updating grades');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating grades.');
            });
        });

        document.getElementById('addAchievementForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });

            fetch('mentors/add_achievement.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    alert('Achievement added successfully!');
                    closeModal('addAchievementModal');
                } else {
                    alert(result.message || 'Error adding achievement');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding achievement.');
            });
        });

        document.getElementById('addNoteForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });

            fetch('mentors/add_note.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    alert('Note added successfully!');
                    closeModal('addNoteModal');
                } else {
                    alert(result.message || 'Error adding note');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding note.');
            });
        });

        // Add event listener for clicking outside modal to close it
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal(event.target.id);
            }
        }

        // Add live grade calculation
        function calculateGrade() {
            const assignmentScore = parseFloat(document.getElementById('assignment_score').value) || 0;
            const midtermScore = parseFloat(document.getElementById('midterm_score').value) || 0;
            const finalScore = parseFloat(document.getElementById('final_score').value) || 0;

            if (assignmentScore >= 0 && midtermScore >= 0 && finalScore >= 0) {
                const totalScore = (assignmentScore * 0.3) + (midtermScore * 0.3) + (finalScore * 0.4);
                let gradeLetter = 'F';
                if (totalScore >= 90) gradeLetter = 'A';
                else if (totalScore >= 80) gradeLetter = 'B';
                else if (totalScore >= 70) gradeLetter = 'C';
                else if (totalScore >= 60) gradeLetter = 'D';

                document.getElementById('grade_summary').style.display = 'block';
                document.getElementById('total_score').textContent = totalScore.toFixed(2);
                document.getElementById('grade_letter').textContent = gradeLetter;
            }
        }

        // Add event listeners for live grade calculation
        document.getElementById('assignment_score').addEventListener('input', calculateGrade);
        document.getElementById('midterm_score').addEventListener('input', calculateGrade);
        document.getElementById('final_score').addEventListener('input', calculateGrade);
    </script>
</body>
</html>
