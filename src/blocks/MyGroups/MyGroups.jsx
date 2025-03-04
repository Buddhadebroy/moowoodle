import React, { useState, useEffect } from "react";
import axios from "axios";
import { getApiLink } from "../../services/apiService";
import ViewEnroll from "../ViewEnroll/ViewEnroll";
import "./mygroups.scss";

const MyGroups = () => {
  const [groups, setGroups] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [selectedItem, setSelectedItem] = useState(null); // Stores full item object with order_id

  useEffect(() => {
    fetchGroups();
  }, []);

  const fetchGroups = async () => {
    try {
      setLoading(true); // Show loading before fetching
      const response = await axios.post(getApiLink("get-user-groups"), {}, {
        headers: { "X-WP-Nonce": appLocalizer.nonce },
      });

      setGroups(response.data.groups);
      setLoading(false);
    } catch (error) {
      console.error("Error fetching groups:", error);
      setError("Failed to load groups.");
      setLoading(false);
    }
  };

  const handleViewEnroll = (group, item) => {
    // Combine item data with order_id from the parent group
    setSelectedItem({
      ...item,
      order_id: group.order_id,
    });
  };

  const handleBack = () => {
    setSelectedItem(null); // Clear selected item
    setGroups([]); // Reset groups to trigger re-render
    fetchGroups(); // Reload groups data
  };

  if (loading) return <p>Loading groups...</p>;
  if (error) return <p>{error}</p>;

  // If an item is selected, render the ViewEnroll component with full item data including order_id
  if (selectedItem) {
    return <ViewEnroll item={selectedItem} onBack={handleBack} />;
  }

  return (
    <div>

      {groups.length > 0 ? (
        groups.map((group) => (
          <div key={group.group_id}>
            <h2>{group.group_name}</h2>
            <table className="moowoodle-table woocommerce-MyAccount-orders woocommerce-orders-table shop_table shop_table_responsive my_account_orders account-orders-table">
              <thead>
                <tr className="woocommerce-orders-table__row">
                  <th className="woocommerce-orders-table__header">Course ID</th>
                  <th className="woocommerce-orders-table__header">Product ID</th>
                  <th className="woocommerce-orders-table__header">Total Quantity</th>
                  <th className="woocommerce-orders-table__header">Available Quantity</th>
                  <th className="woocommerce-orders-table__header">Status</th>
                  <th className="woocommerce-orders-table__header">Enroll User</th>
                </tr>
              </thead>
              <tbody>
                {group.items.map((item) => (
                  <tr className="woocommerce-orders-table__row" key={item.id}>
                    <td className="woocommerce-orders-table__cell">{item.course_id}</td>
                    <td className="woocommerce-orders-table__cell">{item.product_id}</td>
                    <td className="woocommerce-orders-table__cell">{item.total_quantity}</td>
                    <td className="woocommerce-orders-table__cell">{item.available_quantity}</td>
                    <td className="woocommerce-orders-table__cell">{item.status}</td>
                    <td>
                      <button
                        onClick={() => handleViewEnroll(group, item)}
                        className="woocommerce-button wp-element-button button view"
                      >
                        View Enroll
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ))
      ) : (
        <div className="woocommerce-info">
          You have no groups yet.
        </div>
      )}
    </div>
  );
};

export default MyGroups;
