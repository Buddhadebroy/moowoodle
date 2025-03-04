import React, { useState, useEffect } from "react";
import axios from "axios";
import { getApiLink } from "../../services/apiService";
import "../MyGroups/mygroups.scss";

const ViewEnroll = ({ item, onBack }) => {
  const [enrolledUsers, setEnrolledUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState(""); // New state for success messages
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [newUser, setNewUser] = useState({ name: "", email: "" });

  useEffect(() => {
    fetchEnrollments();
  }, [item.id]);

  const fetchEnrollments = async () => {
    try {
      setLoading(true);
      const response = await axios.post(
        getApiLink("get-user-enrollments-by-group-item-id"),
        { group_item_id: item.id },
        { headers: { "X-WP-Nonce": appLocalizer.nonce } }
      );
      setEnrolledUsers(response.data.enrollments);
    } catch (error) {
      setError("Failed to load enrollments.");
    } finally {
      setLoading(false);
    }
  };

  const openModal = () => {
    setError("");
    setSuccess(""); // Clear previous messages when opening modal
    setIsModalOpen(true);
  };

  const closeModal = () => {
    setNewUser({ name: "", email: "" });
    setIsModalOpen(false);
  };

  const handleChange = (e) => {
    setNewUser({ ...newUser, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    setError("");
    setSuccess(""); // Clear previous messages

    try {
      const response = await axios.post(
        getApiLink("enroll-user"),
        {
          group_item_id: item.id,
          name: newUser.name,
          email: newUser.email,
          course_id: item.course_id,
          order_id: item.order_id,
        },
        { headers: { "X-WP-Nonce": appLocalizer.nonce } }
      );

      if (response.data.success) {
        setSuccess(response.data.message || "User enrolled successfully.");
        await fetchEnrollments();
        setTimeout(closeModal, 2000); // Auto-close modal after 2 seconds
      } else {
        setError(response.data.message || "Enrollment failed.");
      }
    } catch (error) {
      setError("Failed to enroll user.");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="enroll-container">

      {loading ? <p>Loading enrollments...</p> : error ? <p className="error-message">{error}</p> : (
        <>
          <div class="button-wrapper">
              <button onClick={onBack} className="woocommerce-Button button wp-element-button">← Back to Groups</button>
              <button onClick={openModal} className="woocommerce-Button button wp-element-button">
                + Add User
              </button>
          </div>
          <div className="table-container">
            <table className="moowoodle-table woocommerce-MyAccount-orders woocommerce-orders-table shop_table shop_table_responsive my_account_orders account-orders-table">
              <thead>
                <tr className="woocommerce-orders-table__row">
                  <th className="woocommerce-orders-table__header">Name</th>
                  <th className="woocommerce-orders-table__header">Email</th>
                  <th className="woocommerce-orders-table__header">Enrollment Date</th>
                </tr>
              </thead>
              <tbody>
                {enrolledUsers.length > 0 ? (
                  enrolledUsers.map((user, index) => (
                    <tr key={index} className="woocommerce-orders-table__row">
                      <td className="woocommerce-orders-table__cell">{user.name}</td>
                      <td className="woocommerce-orders-table__cell">{user.email}</td>
                      <td className="woocommerce-orders-table__cell">{user.date}</td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan="3" className="no-data">No enrollment data available.</td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </>
      )}

      {isModalOpen && (
        <>
          <div className="modal-overlay" onClick={closeModal}></div>
          <div className="modal-content">
            <form onSubmit={handleSubmit}>
              <fieldset>
                <legend>Add New User</legend>
                  <p className="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label>Name</label>
                    <input className="woocommerce-Input woocommerce-Input--text input-text" type="text" name="name" value={newUser.name} onChange={handleChange} required disabled={submitting} />
                  </p>
                  <p className="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label>Email</label>
                    <input className="woocommerce-Input woocommerce-Input--text input-text" type="email" name="email" value={newUser.email} onChange={handleChange} required disabled={submitting} />
                  </p>
              </fieldset>
              {/* Show success or error messages */}
              {error && <p className="error-message">{error}</p>}
              {success && <p className="success-message">{success}</p>}

              <div className="modal-actions">
                <button type="submit" className="woocommerce-Button button wp-element-button" disabled={submitting}>
                  {submitting ? "Adding..." : "Add"}
                </button>
                <button type="button" className="woocommerce-Button button wp-element-button" onClick={closeModal} disabled={submitting}>
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </>
      )}
    </div>
  );
};

export default ViewEnroll;
