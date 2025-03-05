import React, { useEffect, useState } from "react";
import axios from "axios";
import { getApiLink } from "../../services/apiService";

const MyCourse = () => {
  const [courses, setCourses] = useState([]);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const perPage = 5;
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    fetchCourses(currentPage);
  }, [currentPage]);

  const fetchCourses = async (page) => {
    setLoading(true);
    try {
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
    setLoading(false);
  };

  return (
    <div className="auto">
      {courses.length > 0 && <p>Total Courses: {courses.length}</p>}

      <table className="moowoodle-table shop_table shop_table_responsive my_account_orders">
        <thead>
          <tr>
            <th>Username</th>
            <th>Enrolment Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          {loading ? (
            <tr>
              <td colSpan="3" className="loading-row">Loading...</td>
            </tr>
          ) : courses.length > 0 ? (
            courses.map((course, index) => (
              <tr key={index}>
                <td>{course.user_login}</td> 
                <td>{course.enrolment_date}</td> 
                <td>
                  <a
                    target="_blank"
                    rel="noopener noreferrer"
                    className="woocommerce-button wp-element-button moowoodle"
                    href={course.moodle_url}
                  >
                    View
                  </a>
                </td>
              </tr>
            ))
          ) : (
            <tr>
              <td colSpan="3" className="no-data-row">You haven't purchased any courses yet.</td>
            </tr>
          )}
        </tbody>
      </table>

      {/* Pagination Controls */}
      {totalPages > 1 && (
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
      )}
    </div>
  );
};

export default MyCourse;
