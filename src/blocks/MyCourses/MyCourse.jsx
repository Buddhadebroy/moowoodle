import React, { useEffect, useState } from "react";
import axios from "axios";
import { getApiLink } from "../../services/apiService";
import "./mycourse.scss"


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
    <div className="">
      {courses.length > 0 && <p>Total Courses: {courses.length}</p>}

      {courses.length > 0 ? (
        <>
          <table className="moowoodle-table woocommerce-MyAccount-orders woocommerce-orders-table shop_table shop_table_responsive my_account_orders account-orders-table">
            <thead>
              <tr className="woocommerce-orders-table__row">
                <th className="woocommerce-orders-table__header">Course ID</th>
                <th className="woocommerce-orders-table__header">Username</th>
                <th className="woocommerce-orders-table__header">Password</th>
                <th className="woocommerce-orders-table__header">Enrolment Date</th>
                <th className="woocommerce-orders-table__header">Action</th>
              </tr>
            </thead>
            <tbody>
              {courses.map((course, index) => (
                <tr className="woocommerce-orders-table__row" key={index}>
                  <td className="woocommerce-orders-table__cell">{course.course_id}</td>
                  <td className="woocommerce-orders-table__cell">{course.user_login}</td>
                  <td className="woocommerce-orders-table__cell">{course.password}</td>
                  <td className="woocommerce-orders-table__cell">{course.enrolment_date}</td>
                  <td>
                    <a
                      target="_blank"
                      rel="noopener noreferrer"
                      className="woocommerce-button wp-element-button button view"
                      href={course.moodle_url}
                    >
                      View
                    </a>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {/* Pagination Controls */}
          <div className="woocommerce-Pagination">
            <button disabled={currentPage === 1} onClick={() => setCurrentPage((prev) => prev - 1)} className="woocommerce-button woocommerce-button--previous">
              Previous
            </button>

            <button disabled={currentPage === totalPages} onClick={() => setCurrentPage((prev) => prev + 1)} className="woocommerce-button woocommerce-button--next">
              Next
            </button>
            
            <span>
              Page {currentPage} of {totalPages}
            </span>
          </div>
        </>
      ) : (
        <div className="woocommerce-info">
          You haven't purchased any courses yet.
        </div>
      )}


    </div>
  );
};

export default MyCourse;
