import React, { useState, useEffect } from "react";
import axios from "axios";
import Select from "react-select";
import { getApiLink } from "../../services/apiService";
import "./MyClassroom.scss";

const ViewEnroll = ({ classroom, onBack }) => {
    const [enrolledStudents, setEnrolledStudents] = useState([]);
    const [showForm, setShowForm] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [enrollmentMessages, setEnrollmentMessages] = useState([]);
    const [newStudent, setNewStudent] = useState({ name: "", email: "", courses: [] });
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const studentsPerPage = 5; // Matches backend default

    // Generate course options dynamically from classroom.items
    const courseOptions = classroom.items.map((item) => ({
        value: item.course_id,
        label: item.course_name,
        group_item_id: item.id,
    }));

    // Fetch enrolled students dynamically with pagination
    const fetchEnrolledStudents = async (page = 1) => {
        if (!classroom.items.length) {
            setEnrolledStudents([]);
            setTotalPages(1);
            return;
        }

        try {
            const groupItemIds = classroom.items.map((item) => item.id);
            const response = await axios.get(getApiLink("get-classroom-enrollments"), {
                params: {
                    group_item_ids: groupItemIds,
                    page: page,
                    per_page: studentsPerPage,
                },
                headers: { "X-WP-Nonce": appLocalizer.nonce },
            });

            const data = response.data;
            setEnrolledStudents(data.enrollments || []);
            setTotalPages(data.total_pages || 1);
            setCurrentPage(data.current_page || page);
        } catch (error) {
            console.error("Error fetching enrolled students:", error);
            setEnrolledStudents([]);
            setTotalPages(1);
        }
    };

    // Fetch data on mount or when classroom.items changes
    useEffect(() => {
        fetchEnrolledStudents(currentPage);
    }, [classroom.items]);

    // Handle input changes
    const handleInputChange = (e) => {
        setNewStudent({ ...newStudent, [e.target.name]: e.target.value });
    };

    // Handle course selection
    const handleCourseChange = (selectedOptions) => {
        const courses = selectedOptions
            ? selectedOptions.map((option) => ({
                  course_id: option.value,
                  group_item_id: option.group_item_id,
                  course_name: option.label,
              }))
            : [];
        setNewStudent({ ...newStudent, courses });
    };

    // Handle student enrollment
    const handleEnrollStudent = async (e) => {
        e.preventDefault();

        if (!newStudent.name || !newStudent.email || !newStudent.courses.length) {
            alert("Please fill in all fields.");
            return;
        }

        setIsLoading(true);

        const payload = {
            email: newStudent.email,
            name: newStudent.name,
            order_id: classroom.order_id || 0,
            course_selections: newStudent.courses.map((course) => ({
                course_id: course.course_id,
                group_item_id: course.group_item_id,
            })),
        };

        try {
            const response = await axios.post(getApiLink("enroll-user"), payload, {
                headers: { "X-WP-Nonce": appLocalizer.nonce },
            });

            if (response.data.success) {
                setEnrollmentMessages(response.data.enrolled_courses || []);
                setShowForm(false);
                setNewStudent({ name: "", email: "", courses: [] });
                setCurrentPage(1); // Reset to first page after enrollment
                await fetchEnrolledStudents(1); // Fetch first page
            } else {
                alert("Enrollment failed: " + (response.data.message || "Unknown error"));
            }
        } catch (error) {
            console.error("Error enrolling student:", error);
            alert("Error enrolling student. Please try again.");
        }

        setIsLoading(false);
    };

    // Handle page change
    const handlePageChange = (page) => {
        if (page >= 1 && page <= totalPages) {
            setCurrentPage(page);
            fetchEnrolledStudents(page);
        }
    };

    return (
        <div className="enrollment-container">
            <button className="back-button" onClick={onBack}>← Back to Classrooms</button>
            <h1>Enrolled Students for {classroom.group_name}</h1>

            <button className="enroll-button" onClick={() => setShowForm(!showForm)}>
                {showForm ? "Cancel" : "+ Enroll Student"}
            </button>

            {showForm && (
                <form className="enroll-form" onSubmit={handleEnrollStudent}>
                    <input
                        type="text"
                        name="name"
                        placeholder="Student Name"
                        value={newStudent.name}
                        onChange={handleInputChange}
                        required
                    />
                    <input
                        type="email"
                        name="email"
                        placeholder="Student Email"
                        value={newStudent.email}
                        onChange={handleInputChange}
                        required
                    />
                    <Select
                        isMulti
                        options={courseOptions}
                        placeholder="Select Courses"
                        value={courseOptions.filter((option) =>
                            newStudent.courses.some((course) => course.course_id === option.value)
                        )}
                        onChange={handleCourseChange}
                    />
                    <button type="submit" className="save-button" disabled={isLoading}>
                        {isLoading ? "Enrolling..." : "Enroll"}
                    </button>
                </form>
            )}

            {enrollmentMessages.length > 0 && (
                <div className="enrollment-messages">
                    <h3>Enrollment Status:</h3>
                    <ul>
                        {enrollmentMessages.map((course, index) => (
                            <li
                                key={index}
                                className={course.message.includes("failed") ? "error" : "success"}
                            >
                                {course.course_id}: {course.message}
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            <div className="student-list">
                {enrolledStudents.length > 0 ? (
                    enrolledStudents.map((student, index) => (
                        <div key={index} className="student-card">
                            <h2>{student.name}</h2>
                            <p><strong>Email:</strong> {student.email}</p>
                            <p><strong>Enrolled:</strong> {student.date}</p>
                        </div>
                    ))
                ) : (
                    <p>No students enrolled yet.</p>
                )}
            </div>

            {/* Pagination Controls */}
            {totalPages > 1 && (
                <div className="pagination">
                    <button
                        onClick={() => handlePageChange(currentPage - 1)}
                        disabled={currentPage === 1}
                    >
                        Previous
                    </button>
                    <span>
                        Page {currentPage} of {totalPages}
                    </span>
                    <button
                        onClick={() => handlePageChange(currentPage + 1)}
                        disabled={currentPage === totalPages}
                    >
                        Next
                    </button>
                </div>
            )}
        </div>
    );
};

export default ViewEnroll;