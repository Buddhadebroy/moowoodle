import React, { useEffect, useState } from "react";
import axios from "axios";
import { getApiLink } from "../../services/apiService";

const MyCourse = () => {
  const [courses, setCourses] = useState([]);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const perPage = 5;

  useEffect(() => {
    fetchCourses(currentPage);
  }, [currentPage]);

  const fetchCourses = async (page) => {
    try {
        console.log("Fetching courses...");

        const response = await axios.post(getApiLink("get-user-courses"), {
            page: page,
            row: perPage,
        }, {
            headers: { "X-WP-Nonce": appLocalizer.nonce },
        });

        setCourses(response.data.courses);
        setTotalPages(response.data.total_pages);
    } catch (error) {
        console.error("Error fetching courses:", error);
    }
};


  return (
    <div className="auto">
      {courses.length > 0 && <p>Total Courses: {courses.length}</p>}

      <table className="moowoodle-table shop_table shop_table_responsive my_account_orders">
        <thead>
          <tr>
            <th>Course Name</th>
            <th>Username</th>
            <th>Password</th>
            <th>Enrolment Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          {courses.length > 0 ? (
            courses.map((order) => (
              <tr key={order.id}>
                <td>{order.productName}</td>
                <td>{order.userLogin}</td>
                <td>{order.password}</td>
                <td>{order.enrolmentDate}</td>
                <td>
                  <a
                    target="_blank"
                    rel="noopener noreferrer"
                    className="woocommerce-button wp-element-button moowoodle"
                    href={order.moodleCourseUrl}
                  >
                    View
                  </a>
                </td>
              </tr>
            ))
          ) : (
            <tr>
              <td colSpan="5" className="no-data-row">You haven't purchased any courses yet.</td>
            </tr>
          )}
        </tbody>
      </table>

      {/* Pagination Controls */}
      <div className="pagination">
        <button disabled={currentPage === 1} onClick={() => setCurrentPage((prev) => prev - 1)}>
          Previous
        </button>
        <span>
          Page {currentPage} of {totalPages}
        </span>
        <button disabled={currentPage === totalPages} onClick={() => setCurrentPage((prev) => prev + 1)}>
          Next
        </button>
      </div>
    </div>
  );
};

export default MyCourse;
